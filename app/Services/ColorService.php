<?php

namespace App\Services;

use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

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
     * Swatch values edited here are held in the database only. The catalog
     * import rebuilds colors from database/seed-data/color-hex.csv, so a value
     * that should survive a re-import belongs in that file too.
     */
    public function update(Color $color, array $data): Color
    {
        $color->update($data);

        return $color->fresh('line');
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
