<?php

namespace App\Services;

use App\Models\ValueObservation;
use App\Models\Variant;
use Illuminate\Support\Collection;

/**
 * Resolves what a variant is worth from the observation history.
 *
 * Observations are recorded against a product, optionally narrowed to a color.
 * The most specific match wins, and within the same specificity the most recent
 * observation wins. Condition is never applied as a multiplier.
 */
class ValuationService extends BaseService
{
    /**
     * Resolve the governing observation for many variants in two queries.
     *
     * @param  Collection<int, Variant>  $variants
     * @return array<int, ?ValueObservation> keyed by variant id
     */
    public function resolveMany(Collection $variants): array
    {
        if ($variants->isEmpty()) {
            return [];
        }

        $observations = ValueObservation::whereIn('product_id', $variants->pluck('product_id')->unique())
            ->orderByDesc('observed_on')
            ->orderByDesc('id')
            ->get();

        $byVariant = [];
        $byShape = [];

        foreach ($observations as $observation) {
            if ($observation->color_id === null) {
                $byShape[$observation->product_id] ??= $observation;

                continue;
            }

            $byVariant[$observation->product_id.'|'.$observation->color_id] ??= $observation;
        }

        $resolved = [];

        foreach ($variants as $variant) {
            $resolved[$variant->id] = $byVariant[$variant->product_id.'|'.$variant->color_id]
                ?? $byShape[$variant->product_id]
                ?? null;
        }

        return $resolved;
    }

    public function resolve(Variant $variant): ?ValueObservation
    {
        return $this->resolveMany(collect([$variant]))[$variant->id] ?? null;
    }

    /**
     * Every observation that could apply to this variant, newest first. This is
     * the value-over-time series.
     *
     * @return Collection<int, ValueObservation>
     */
    public function history(Variant $variant): Collection
    {
        return ValueObservation::where('product_id', $variant->product_id)
            ->where(fn ($query) => $query->where('color_id', $variant->color_id)->orWhereNull('color_id'))
            ->orderByDesc('observed_on')
            ->orderByRaw('color_id is null')
            ->get();
    }

    /**
     * Attach the resolved observation to each variant so resources can read it
     * without triggering a query per row.
     *
     * @param  Collection<int, Variant>  $variants
     */
    public function attach(Collection $variants): void
    {
        $resolved = $this->resolveMany($variants);

        foreach ($variants as $variant) {
            $variant->setRelation('resolvedValue', $resolved[$variant->id] ?? null);
        }
    }
}
