<?php

namespace App\Services\Sources;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Models\ExternalListing;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\BaseService;
use App\Services\ColorService;
use App\Services\Import\SeedDataReader;
use App\Services\ProductService;
use Illuminate\Support\Collection;

/**
 * Records the owner's rulings on the names a source uses. A ruling is made
 * once per name, and every listing using that name follows it.
 *
 * Creating a color or product from here only ever uses what the owner typed.
 * Nothing about a color's years or a product's name is taken from the source.
 */
class ListingRulingService extends BaseService
{
    public function __construct(
        private ColorService $colorService,
        private ProductService $productService,
    ) {}

    /**
     * @return Collection<int, object{name_key: string, name: string, listings: int, example: string}>
     */
    public function pendingColors(ListingSource $source): Collection
    {
        return $this->pending($source, 'color', ColorAlias::class);
    }

    /**
     * @return Collection<int, object{name_key: string, name: string, listings: int, example: string}>
     */
    public function pendingProducts(ListingSource $source): Collection
    {
        return $this->pending($source, 'product', ProductAlias::class);
    }

    public function mapColor(ListingSource $source, string $key, Color $color): ColorAlias
    {
        return ColorAlias::updateOrCreate(
            ['source' => $source, 'external_key' => $key],
            ['decision' => AliasDecision::Mapped, 'color_id' => $color->id],
        );
    }

    public function ignoreColor(ListingSource $source, string $key): ColorAlias
    {
        return ColorAlias::updateOrCreate(
            ['source' => $source, 'external_key' => $key],
            ['decision' => AliasDecision::Ignored, 'color_id' => null],
        );
    }

    public function createColor(ListingSource $source, string $key, string $name, ?int $producedFrom, ?int $producedTo): ColorAlias
    {
        $color = $this->colorService->create([
            'line_id' => $this->fiesta()->id,
            'name' => $name,
            'produced_from' => $producedFrom,
            'produced_to' => $producedTo,
        ]);

        return $this->mapColor($source, $key, $color);
    }

    public function mapProduct(ListingSource $source, string $key, Product $product): ProductAlias
    {
        return ProductAlias::updateOrCreate(
            ['source' => $source, 'external_key' => $key],
            ['decision' => AliasDecision::Mapped, 'product_id' => $product->id],
        );
    }

    public function ignoreProduct(ListingSource $source, string $key): ProductAlias
    {
        return ProductAlias::updateOrCreate(
            ['source' => $source, 'external_key' => $key],
            ['decision' => AliasDecision::Ignored, 'product_id' => null],
        );
    }

    public function createProduct(ListingSource $source, string $key, string $name): ProductAlias
    {
        $product = $this->productService->create([
            'line_id' => $this->fiesta()->id,
            'name' => $name,
        ]);

        return $this->mapProduct($source, $key, $product);
    }

    /**
     * Colors in the line the store sells, labeled with their years so reused
     * names can be told apart.
     *
     * @return array<int, string> keyed by color id
     */
    public function colorChoices(string $search = ''): array
    {
        return Color::where('line_id', $this->fiesta()->id)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('produced_from')
            ->get()
            ->mapWithKeys(fn (Color $color) => [$color->id => $color->name.' ('.($color->produced_label ?? 'years unknown').')'])
            ->all();
    }

    /**
     * @return array<int, string> keyed by product id
     */
    public function productChoices(string $search = ''): array
    {
        return Product::where('line_id', $this->fiesta()->id)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  class-string<ColorAlias|ProductAlias>  $model
     */
    private function pending(ListingSource $source, string $kind, string $model): Collection
    {
        $ruled = $model::where('source', $source)->pluck('external_key');

        return ExternalListing::where('source', $source)
            ->whereNotIn("{$kind}_key", $ruled)
            ->selectRaw("{$kind}_key as name_key, min({$kind}_name) as name, count(*) as listings, min(title) as example")
            ->groupBy("{$kind}_key")
            ->orderByDesc('listings')
            ->orderBy('name_key')
            ->get()
            ->map(fn ($row) => (object) [
                'name_key' => $row->name_key,
                'name' => $row->name,
                'listings' => (int) $row->listings,
                'example' => $row->example,
            ]);
    }

    private function fiesta(): Line
    {
        return Line::where('name', SeedDataReader::LINE_FIESTA)->firstOrFail();
    }
}
