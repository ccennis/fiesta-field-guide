<?php

namespace App\Services;

use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ColorService extends BaseService
{
    /**
     * Create a color and its share of the catalog. Like a new product, a new
     * color has to be crossed with its line's products or it could never be
     * identified or evidenced.
     */
    public function create(array $data): Color
    {
        return DB::transaction(function () use ($data) {
            $color = Color::create(['name' => self::titleCase($data['name'])] + $data);
            $this->buildVariants($color);

            return $color->fresh('line');
        });
    }

    /**
     * Swatch values and production years edited here are held in the database
     * only. The catalog import rebuilds colors from database/seed-data, so a
     * value that should survive a re-import belongs in those files too.
     */
    public function update(Color $color, array $data): Color
    {
        if (array_key_exists('produced_from', $data)) {
            $clash = Color::where('line_id', $color->line_id)
                ->where('name', $color->name)
                ->where('produced_from', $data['produced_from'])
                ->whereKeyNot($color->id)
                ->exists();

            if ($clash) {
                throw new RuntimeException("There is already a {$color->name} starting in {$data['produced_from']}.");
            }
        }

        $color->update($data);

        return $color->fresh('line');
    }

    /**
     * Every product in the color's line, as the plain piece in this color,
     * with what the owner and the store know about it.
     *
     * @return Collection<int, Variant>
     */
    public function checklist(Color $color): Collection
    {
        $products = Product::where('line_id', $color->line_id)->orderBy('name')->get();

        foreach ($products as $product) {
            $this->plainVariant($color, $product);
        }

        $ownerId = User::admin()?->id;

        return Variant::with('product')
            ->withCount(['evidence', 'holdings' => fn (Builder $holdings) => $holdings->where('user_id', $ownerId)])
            ->where('color_id', $color->id)
            ->whereNull('decoration_id')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->orderBy('products.name')
            ->addSelect('variants.*')
            ->get();
    }

    /**
     * The owner's word on whether a product was made in a color. Saying it was
     * confirms the piece and is remembered, so it outlasts the store listings.
     * Taking it back only works while nothing else vouches for the piece.
     */
    public function setMade(Color $color, Product $product, bool $made): Variant
    {
        if ($product->line_id !== $color->line_id) {
            throw new RuntimeException("{$product->name} is not in the same line as {$color->name}.");
        }

        $variant = $this->plainVariant($color, $product);

        if ($made) {
            $variant->update([
                'existence' => VariantExistence::Confirmed,
                'confirmed_by_owner_at' => $variant->confirmed_by_owner_at ?? now(),
            ]);
        } else {
            $ownerId = User::admin()?->id;

            if ($variant->holdings()->where('user_id', $ownerId)->exists()) {
                throw new RuntimeException("You own a {$color->name} {$product->name}, so it stays verified.");
            }

            if ($variant->evidence()->exists()) {
                throw new RuntimeException("A store listing shows a {$color->name} {$product->name}, so it stays verified.");
            }

            $variant->update(['existence' => VariantExistence::Unconfirmed, 'confirmed_by_owner_at' => null]);
        }

        return $variant->load('product')->loadCount([
            'evidence',
            'holdings' => fn (Builder $holdings) => $holdings->where('user_id', User::admin()?->id),
        ]);
    }

    /**
     * Color names start each word with a capital ("Cobalt Blue", "Red (Orange
     * Red)"). The store sometimes writes them in lower case. Letters already
     * capitalized are left alone.
     */
    public static function titleCase(string $name): string
    {
        return ucwords(trim(preg_replace('/\s+/', ' ', $name)), " -(\t");
    }

    /**
     * Every plain pairing inside a line is generated when a product or color is
     * made, so this normally finds one. It creates one if a pairing is missing.
     */
    private function plainVariant(Color $color, Product $product): Variant
    {
        return Variant::firstOrCreate(
            ['product_id' => $product->id, 'color_id' => $color->id, 'decoration_id' => null],
            ['existence' => VariantExistence::Unconfirmed]
        );
    }

    private function buildVariants(Color $color): void
    {
        $now = now();

        $rows = Product::where('line_id', $color->line_id)
            ->pluck('id')
            ->map(fn (int $productId) => [
                'product_id' => $productId,
                'color_id' => $color->id,
                'existence' => VariantExistence::Unconfirmed->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Variant::insertOrIgnore($chunk);
        }
    }
}
