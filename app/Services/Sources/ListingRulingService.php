<?php

namespace App\Services\Sources;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
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
 * Products take the store's wording once a store name is mapped to them, as
 * the owner chose. A color's years are only ever what the owner typed.
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

    /**
     * Rename a product to the store's wording. When several store names map to
     * one product, the one on the most listings wins, in its most common
     * spelling. A name already used by another product is never taken; the
     * two are more likely the same piece and should be merged.
     *
     * @return array{from: ?string, to: ?string, conflict: ?string}
     */
    public function adoptStoreName(ListingSource $source, Product $product): array
    {
        $result = ['from' => null, 'to' => null, 'conflict' => null];

        $keys = ProductAlias::where('source', $source)
            ->where('decision', AliasDecision::Mapped)
            ->where('product_id', $product->id)
            ->pluck('external_key');

        $listings = ExternalListing::where('source', $source)->whereIn('product_key', $keys)->get(['product_key', 'product_name']);

        if ($listings->isEmpty()) {
            return $result;
        }

        $busiest = $listings->countBy('product_key')->sortDesc()->keys()->first();
        $name = $listings->where('product_key', $busiest)->pluck('product_name')->countBy()->sortDesc()->keys()->first();

        if ($name === $product->name) {
            return $result;
        }

        $taken = Product::where('line_id', $product->line_id)
            ->where('name', $name)
            ->whereKeyNot($product->id)
            ->exists();

        if ($taken) {
            $result['conflict'] = "Another product is already called \"{$name}\". Merge the two on the Products screen if they are the same piece.";

            return $result;
        }

        $result['from'] = $product->name;
        $this->productService->update($product, ['name' => $name]);
        $result['to'] = $name;

        return $result;
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
     * Remove a ruling, so the name goes back to awaiting one.
     */
    public function undo(ListingSource $source, ListingNameKind $kind, string $key): void
    {
        $model = $kind === ListingNameKind::Color ? ColorAlias::class : ProductAlias::class;

        $model::where('source', $source)->where('external_key', $key)->delete();
    }

    /**
     * The line the store sells. Every ruling from this source points into it.
     */
    public function fiesta(): Line
    {
        return Line::where('name', SeedDataReader::LINE_FIESTA)->firstOrFail();
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
                'name' => $kind === 'color' ? ColorService::titleCase($row->name) : $row->name,
                'listings' => (int) $row->listings,
                'example' => $row->example,
            ]);
    }
}
