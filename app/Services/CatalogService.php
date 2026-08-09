<?php

namespace App\Services;

use App\Enums\Era;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Decoration;
use App\Models\Holding;
use App\Models\Line;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reads over the catalog: the pickers that drive identification, the filtered
 * variant list that backs both the collection view and the missing view, and
 * the single variant answer.
 */
class CatalogService extends BaseService
{
    public function __construct(
        private ValuationService $valuationService,
    ) {}

    /** @return Collection<int, Line> */
    public function lines(): Collection
    {
        return Line::orderBy('name')->get();
    }

    /** @return Collection<int, Decoration> */
    public function decorations(): Collection
    {
        return Decoration::orderBy('name')->get();
    }

    /**
     * Products for the first step of identification, and for the management
     * screen. The counts say how much catalog and how many pieces hang off each
     * one, which is what makes a merge safe to judge before running it.
     *
     * @param  array<string, mixed>  $input
     * @return Collection<int, Product>
     */
    public function products(array $input = []): Collection
    {
        $lineId = isset($input['line_id']) ? (int) $input['line_id'] : null;

        return Product::with('line')
            ->withCount('variants')
            ->withCount(['variants as pieces_count' => fn ($query) => $query
                ->join('holdings', 'holdings.variant_id', '=', 'variants.id')])
            ->when($lineId, fn (Builder $query, $id) => $query->where('line_id', $id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Colors for the second step. When a product is given, only colors that pair
     * with it inside the same line are returned.
     *
     * @param  array<string, mixed>  $input
     * @return Collection<int, Color>
     */
    public function colors(array $input = []): Collection
    {
        $lineId = isset($input['line_id']) ? (int) $input['line_id'] : null;
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : null;
        $era = isset($input['era']) ? Era::from($input['era']) : null;

        return Color::with('line')
            ->when($lineId, fn (Builder $query, $id) => $query->where('line_id', $id))
            ->when($era, fn (Builder $query, $value) => $this->constrainEra($query, $value))
            ->when($productId, fn (Builder $query, $id) => $query->whereHas(
                'variants',
                fn (Builder $variants) => $variants->where('product_id', $id)
            ))
            ->orderBy('name')
            ->orderBy('produced_from')
            ->get();
    }

    /**
     * The filtered variant list. `owned` flips it between the collection and
     * the missing view; without narrowing filters the missing view is most of
     * the catalog, which is why the filters exist.
     *
     * Takes the raw validated input and does its own casting, so controllers
     * stay a validate-and-hand-off.
     *
     * @param  array<string, mixed>  $input
     */
    public function variants(array $input): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($input);

        $paginator = Variant::query()
            ->with(['product.line', 'color', 'decoration'])
            ->withCount('holdings')
            ->when(
                $filters['line_id'],
                fn (Builder $query, $lineId) => $query->whereHas('product', fn (Builder $product) => $product->where('line_id', $lineId))
            )
            ->when($filters['product_id'], fn (Builder $query, $productId) => $query->where('product_id', $productId))
            ->when($filters['color_id'], fn (Builder $query, $colorId) => $query->where('color_id', $colorId))
            ->when(
                $filters['era'],
                fn (Builder $query, $era) => $query->whereHas('color', fn (Builder $color) => $this->constrainEra($color, $era))
            )
            ->when(
                $filters['year'],
                fn (Builder $query, $year) => $query->whereHas('color', fn (Builder $color) => $this->constrainYear($color, $year))
            )
            ->when(
                $filters['decorated'] !== null,
                fn (Builder $query) => $filters['decorated']
                    ? $query->whereNotNull('decoration_id')
                    : $query->whereNull('decoration_id')
            )
            ->when($filters['decoration_id'], fn (Builder $query, $id) => $query->where('decoration_id', $id))
            ->when(
                $filters['existence'],
                fn (Builder $query, $existence) => $query->where('existence', $existence)
            )
            ->when($filters['owned'] !== null, fn (Builder $query) => $filters['owned']
                ? $query->has('holdings')
                : $query->doesntHave('holdings'))
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->join('colors', 'colors.id', '=', 'variants.color_id')
            ->orderBy('products.name')
            ->orderBy('colors.name')
            ->orderBy('colors.produced_from')
            ->addSelect('variants.*')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $this->valuationService->attach(collect($paginator->items()));

        return $paginator;
    }

    /**
     * The in-shop answer for one variant: what it is, what is known about its
     * rarity, what it is worth, and whether one is already owned.
     */
    public function identify(Variant $variant): Variant
    {
        $variant->load(['product.line', 'color', 'decoration', 'holdings']);
        $variant->loadCount('holdings');
        $this->valuationService->attach(collect([$variant]));
        $variant->setRelation('valueHistory', $this->valuationService->history($variant));

        return $variant;
    }

    /**
     * Headline numbers for the collection screen.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $owned = Variant::with(['product', 'color'])->withCount('holdings')->has('holdings')->get();
        $resolved = $this->valuationService->resolveMany($owned);

        $total = 0.0;
        $unvalued = 0;

        foreach ($owned as $variant) {
            $observation = $resolved[$variant->id] ?? null;

            if ($observation === null) {
                $unvalued += $variant->holdings_count;

                continue;
            }

            $total += (float) $observation->amount * $variant->holdings_count;
        }

        return [
            'holdings' => Holding::count(),
            'variants_owned' => $owned->count(),
            'variants_total' => Variant::count(),
            'variants_confirmed' => Variant::where('existence', VariantExistence::Confirmed)->count(),
            'estimated_value' => round($total, 2),
            'pieces_without_a_value' => $unvalued,
        ];
    }

    /**
     * Cast raw request input into the types the query needs. Enum values and
     * boolean-ish query strings are resolved here rather than in a controller.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $input): array
    {
        $toBool = fn (string $key) => isset($input[$key])
            ? filter_var($input[$key], FILTER_VALIDATE_BOOLEAN)
            : null;

        $toInt = fn (string $key) => isset($input[$key]) ? (int) $input[$key] : null;

        return [
            'line_id' => $toInt('line_id'),
            'product_id' => $toInt('product_id'),
            'color_id' => $toInt('color_id'),
            'decoration_id' => $toInt('decoration_id'),
            'year' => $toInt('year'),
            'per_page' => $toInt('per_page') ?? 50,
            'era' => isset($input['era']) ? Era::from($input['era']) : null,
            'existence' => isset($input['existence']) ? VariantExistence::from($input['existence']) : null,
            'owned' => $toBool('owned'),
            'decorated' => $toBool('decorated'),
        ];
    }

    /**
     * Colors in production during a given year. Colors with no start year are
     * excluded rather than assumed, since their range is unknown.
     */
    private function constrainYear(Builder $query, int $year): Builder
    {
        return $query->whereNotNull('produced_from')
            ->where('produced_from', '<=', $year)
            ->where(fn (Builder $inner) => $inner->whereNull('produced_to')->orWhere('produced_to', '>=', $year));
    }

    private function constrainEra(Builder $query, Era $era): Builder
    {
        [$start, $end] = $era->yearRange();

        return $query->where('produced_from', '>=', $start)
            ->when($end, fn (Builder $inner) => $inner->where('produced_from', '<=', $end));
    }
}
