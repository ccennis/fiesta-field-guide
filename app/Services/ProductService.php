<?php

namespace App\Services;

use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Holding;
use App\Models\Product;
use App\Models\ValueObservation;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Products are seeded from the collection export but owned by the collector
 * after that: renamed, merged as duplicates are spotted, and added as new ones
 * are learned. The export is a starting point, not the authority.
 */
class ProductService extends BaseService
{
    /**
     * Create a product and its share of the catalog. A new product has to be
     * crossed with the line's colors or it would never appear in the missing
     * view, which is the reason for adding it.
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);
            $this->buildVariants($product);

            return $product->fresh('line');
        });
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh('line');
    }

    /**
     * Fold one product into another, keeping every piece.
     *
     * Where both products already cover the same color and decoration, the
     * source's holdings move onto the surviving variant and the now empty
     * variant is dropped. Where only the source covers it, the variant is
     * repointed. Value observations move across; nothing is deleted that has a
     * piece or a figure attached to it.
     *
     * @return array<string, int> what moved, for the caller to report
     */
    public function merge(Product $source, Product $target): array
    {
        if ($source->is($target)) {
            throw new RuntimeException('A product cannot be merged into itself.');
        }

        if ($source->line_id !== $target->line_id) {
            throw new RuntimeException('Products can only be merged within the same line.');
        }

        return DB::transaction(function () use ($source, $target) {
            $moved = ['holdings' => 0, 'variants_repointed' => 0, 'variants_folded' => 0, 'observations' => 0];

            foreach ($source->variants()->with('holdings')->get() as $variant) {
                $existing = Variant::where('product_id', $target->id)
                    ->where('color_id', $variant->color_id)
                    ->where('decoration_id', $variant->decoration_id)
                    ->first();

                if ($existing === null) {
                    $variant->update(['product_id' => $target->id]);
                    $moved['variants_repointed']++;

                    continue;
                }

                $count = Holding::where('variant_id', $variant->id)->update(['variant_id' => $existing->id]);
                $moved['holdings'] += $count;

                if ($count > 0 || $variant->existence === VariantExistence::Confirmed) {
                    $existing->update(['existence' => VariantExistence::Confirmed]);
                }

                $variant->delete();
                $moved['variants_folded']++;
            }

            $moved['observations'] = ValueObservation::where('product_id', $source->id)
                ->update(['product_id' => $target->id]);

            $source->delete();

            return $moved;
        });
    }

    /**
     * Deleting a product takes its variants with it, so it is only allowed
     * when nothing is owned. Merging is the route for a duplicate.
     */
    public function delete(Product $product): void
    {
        $owned = Holding::whereHas('variant', fn ($query) => $query->where('product_id', $product->id))->count();

        if ($owned > 0) {
            throw new RuntimeException("{$product->name} has {$owned} pieces. Merge it into another product instead.");
        }

        $product->delete();
    }

    private function buildVariants(Product $product): void
    {
        $now = now();

        $rows = Color::where('line_id', $product->line_id)
            ->pluck('id')
            ->map(fn (int $colorId) => [
                'product_id' => $product->id,
                'color_id' => $colorId,
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
