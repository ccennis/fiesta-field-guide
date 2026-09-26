<?php

namespace App\Services\Sources;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
use App\Enums\ListingSource;
use App\Enums\VariantExistence;
use App\Models\ColorAlias;
use App\Models\ExternalListing;
use App\Models\ProductAlias;
use App\Models\Variant;
use App\Models\VariantEvidence;
use App\Services\BaseService;
use App\Services\Import\ImportReport;
use Illuminate\Support\Collection;

/**
 * Applies the owner's name rulings to a source's listings.
 *
 * A listing resolves to a variant only when both its product name and its color
 * name are mapped. A resolved listing is recorded as evidence and confirms the
 * variant. When a ruling changes, the old evidence is withdrawn, and a variant
 * left with no evidence and no owned piece goes back to unconfirmed.
 */
class ListingResolver extends BaseService
{
    public function __construct(
        private ListingRulingService $rulings,
    ) {}

    public function resolve(ListingSource $source, ImportReport $report): void
    {
        $newlyConfirmed = $this->apply($source, ExternalListing::where('source', $source)->get(), $report);

        $this->reportTotals($source, $newlyConfirmed, $report);
        $this->reportPending($source, $report);
    }

    /**
     * Re-resolve only the listings that use one name, so a single ruling made
     * on the review screen does not re-check the whole source.
     */
    public function resolveName(ListingSource $source, ListingNameKind $kind, string $key): ImportReport
    {
        $report = new ImportReport;
        $listings = ExternalListing::where('source', $source)->where($kind->keyColumn(), $key)->get();

        $report->set('listings using this name', $listings->count());
        $report->set('variants newly confirmed', $this->apply($source, $listings, $report));
        $report->set('listings resolved to a variant', $listings->filter(fn ($listing) => $listing->variant_id !== null)->count());

        return $report;
    }

    /**
     * @param  Collection<int, ExternalListing>  $listings
     * @return int variants newly confirmed
     */
    private function apply(ListingSource $source, Collection $listings, ImportReport $report): int
    {
        $products = ProductAlias::with('product')->where('source', $source)->get()->keyBy('external_key');
        $colors = ColorAlias::with('color')->where('source', $source)->get()->keyBy('external_key');
        $newlyConfirmed = 0;

        foreach ($listings as $listing) {
            $target = $this->target($listing, $products, $colors, $report);

            if ($listing->variant_id !== $target?->id) {
                $this->withdraw($listing);
                $listing->update(['variant_id' => $target?->id]);
            }

            if ($target === null) {
                continue;
            }

            VariantEvidence::firstOrCreate(['variant_id' => $target->id, 'external_listing_id' => $listing->id]);

            if ($target->existence !== VariantExistence::Confirmed) {
                $target->update(['existence' => VariantExistence::Confirmed]);
                $newlyConfirmed++;
            }
        }

        return $newlyConfirmed;
    }

    /**
     * @param  Collection<string, ProductAlias>  $products
     * @param  Collection<string, ColorAlias>  $colors
     */
    private function target(ExternalListing $listing, Collection $products, Collection $colors, ImportReport $report): ?Variant
    {
        $product = $products->get($listing->product_key);
        $color = $colors->get($listing->color_key);

        if ($product?->decision !== AliasDecision::Mapped || $color?->decision !== AliasDecision::Mapped) {
            return null;
        }

        if ($product->product->line_id !== $color->color->line_id) {
            $report->add(
                'Rulings point at different lines',
                "\"{$listing->title}\": {$product->product->name} and {$color->color->name} belong to different lines."
            );

            return null;
        }

        $variant = Variant::where('product_id', $product->product_id)
            ->where('color_id', $color->color_id)
            ->whereNull('decoration_id')
            ->first();

        if ($variant === null) {
            $report->add(
                'No catalog variant for a ruled pair',
                "\"{$listing->title}\": {$color->color->name} {$product->product->name} is not in the catalog."
            );
        }

        return $variant;
    }

    private function withdraw(ExternalListing $listing): void
    {
        if ($listing->variant_id === null) {
            return;
        }

        VariantEvidence::where('external_listing_id', $listing->id)->delete();

        $variant = Variant::withCount(['evidence', 'holdings'])->find($listing->variant_id);

        if ($variant !== null && $variant->evidence_count === 0 && $variant->holdings_count === 0) {
            $variant->update(['existence' => VariantExistence::Unconfirmed]);
        }
    }

    private function reportTotals(ListingSource $source, int $newlyConfirmed, ImportReport $report): void
    {
        $report->set('listings resolved to a variant', ExternalListing::where('source', $source)->whereNotNull('variant_id')->count());
        $report->set('variants evidenced by this source', ExternalListing::where('source', $source)->whereNotNull('variant_id')->distinct()->count('variant_id'));
        $report->set('variants newly confirmed', $newlyConfirmed);
    }

    /**
     * Unruled names, most listings first, so the first few rulings clear the
     * most ground.
     */
    private function reportPending(ListingSource $source, ImportReport $report): void
    {
        $pending = [
            'color' => $this->rulings->pendingColors($source),
            'product' => $this->rulings->pendingProducts($source),
        ];

        foreach ($pending as $kind => $names) {
            $report->set("{$kind} names awaiting a ruling", $names->count());

            foreach ($names as $row) {
                $report->add(
                    ucfirst($kind).' names awaiting a ruling',
                    "\"{$row->name}\": {$row->listings} ".($row->listings === 1 ? 'listing' : 'listings')
                );
            }
        }
    }
}
