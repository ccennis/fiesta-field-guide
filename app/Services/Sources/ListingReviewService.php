<?php

namespace App\Services\Sources;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
use App\Enums\ListingSource;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Models\ExternalListing;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\BaseService;
use App\Services\Import\ImportReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Backs the review screen: the names a source uses, grouped with a few example
 * listings each, and every ruling the screen can make. Each ruling re-resolves
 * only the listings that use that name.
 */
class ListingReviewService extends BaseService
{
    /** Example listings shown per name. */
    private const EXAMPLES = 4;

    public function __construct(
        private ListingRulingService $rulings,
        private ListingResolver $resolver,
    ) {}

    /**
     * Names awaiting a ruling, or already ruled, most listings first.
     *
     * @return Collection<int, object>
     */
    public function names(ListingSource $source, ListingNameKind $kind, bool $ruled): Collection
    {
        $aliases = $this->aliases($source, $kind);

        return ExternalListing::where('source', $source)
            ->orderBy('is_set')
            ->orderBy('id')
            ->get()
            ->groupBy($kind->keyColumn())
            ->filter(fn (Collection $listings, $key) => $aliases->has((string) $key) === $ruled)
            ->map(fn (Collection $listings, $key) => $this->describe($kind, (string) $key, $listings, $aliases->get((string) $key)))
            ->sortByDesc('listings')
            ->values();
    }

    public function name(ListingSource $source, ListingNameKind $kind, string $key): object
    {
        $listings = ExternalListing::where('source', $source)
            ->where($kind->keyColumn(), $key)
            ->orderBy('is_set')
            ->orderBy('id')
            ->get();

        if ($listings->isEmpty()) {
            throw new RuntimeException('No listing uses that name.');
        }

        return $this->describe($kind, $key, $listings, $this->aliases($source, $kind)->get($key));
    }

    public function rule(ListingSource $source, ListingNameKind $kind, string $key, AliasDecision $decision, ?int $targetId): object
    {
        $this->name($source, $kind, $key);

        if ($decision === AliasDecision::Ignored) {
            $kind === ListingNameKind::Color
                ? $this->rulings->ignoreColor($source, $key)
                : $this->rulings->ignoreProduct($source, $key);

            return $this->result($source, $kind, $key, $this->resolver->resolveName($source, $kind, $key));
        }

        $lineId = $this->rulings->fiesta()->id;
        $target = $kind === ListingNameKind::Color ? Color::find($targetId) : Product::find($targetId);

        if ($target === null || $target->line_id !== $lineId) {
            throw new RuntimeException('Choose a '.mb_strtolower($kind->label()).' from the Fiesta line.');
        }

        $kind === ListingNameKind::Color
            ? $this->rulings->mapColor($source, $key, $target)
            : $this->rulings->mapProduct($source, $key, $target);

        return $this->result($source, $kind, $key, $this->resolver->resolveName($source, $kind, $key));
    }

    /**
     * @param  array{name: string, produced_from?: ?int, produced_to?: ?int}  $data
     */
    public function create(ListingSource $source, ListingNameKind $kind, string $key, array $data): object
    {
        $this->name($source, $kind, $key);

        $report = DB::transaction(function () use ($source, $kind, $key, $data) {
            $kind === ListingNameKind::Color
                ? $this->rulings->createColor($source, $key, $data['name'], $data['produced_from'] ?? null, $data['produced_to'] ?? null)
                : $this->rulings->createProduct($source, $key, $data['name']);

            return $this->resolver->resolveName($source, $kind, $key);
        });

        return $this->result($source, $kind, $key, $report);
    }

    /**
     * Create one new product per product name, each keeping the store's
     * wording, which can be renamed later on the Products screen.
     *
     * @param  array<int, string>  $keys
     * @return int products created
     */
    public function createProducts(ListingSource $source, array $keys): int
    {
        return DB::transaction(function () use ($source, $keys) {
            $created = 0;

            foreach (array_unique($keys) as $key) {
                $name = $this->name($source, ListingNameKind::Product, $key);

                if ($name->ruling !== null) {
                    continue;
                }

                $this->rulings->createProduct($source, $key, $name->name);
                $this->resolver->resolveName($source, ListingNameKind::Product, $key);
                $created++;
            }

            return $created;
        });
    }

    public function undo(ListingSource $source, ListingNameKind $kind, string $key): object
    {
        $this->rulings->undo($source, $kind, $key);

        return $this->result($source, $kind, $key, $this->resolver->resolveName($source, $kind, $key));
    }

    /**
     * The name as it now stands, carrying how many variants the ruling just
     * confirmed so the screen can say so.
     */
    private function result(ListingSource $source, ListingNameKind $kind, string $key, ImportReport $report): object
    {
        $name = $this->name($source, $kind, $key);
        $name->confirmed_now = $report->counts()['variants newly confirmed'] ?? 0;

        return $name;
    }

    /**
     * @return Collection<string, ColorAlias|ProductAlias>
     */
    private function aliases(ListingSource $source, ListingNameKind $kind): Collection
    {
        $query = $kind === ListingNameKind::Color
            ? ColorAlias::with('color')
            : ProductAlias::with('product');

        return $query->where('source', $source)->get()->keyBy('external_key');
    }

    /**
     * @param  Collection<int, ExternalListing>  $listings
     */
    private function describe(ListingNameKind $kind, string $key, Collection $listings, ColorAlias|ProductAlias|null $alias): object
    {
        $nameColumn = $kind->value.'_name';

        return (object) [
            'kind' => $kind,
            'key' => $key,
            // The store spells some names several ways ("White", "white"); show the most common.
            'name' => $listings->pluck($nameColumn)->countBy()->sortDesc()->keys()->first(),
            'listings' => $listings->count(),
            'retired' => $listings->where('is_retired', true)->count(),
            'resolved' => $listings->whereNotNull('variant_id')->count(),
            'examples' => $listings->sortBy(fn ($listing) => $listing->image_url === null)->take(self::EXAMPLES)->values(),
            'ruling' => $alias,
        ];
    }
}
