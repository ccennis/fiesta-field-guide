<?php

namespace App\Http\Resources;

use App\Enums\Era;
use App\Models\Variant;
use Illuminate\Http\Request;

/**
 * A product that matched a browse search. Products carry no years, so its
 * colors are grouped by the era of each color, which is what separates a
 * vintage piece from its reissue. Colors with no known years come last.
 */
class BrowseProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $groups = $this->variants->groupBy(fn (Variant $variant) => $variant->color->era?->value ?? 'unknown');

        $eras = collect([...Era::cases(), null])
            ->map(fn (?Era $era) => [
                'era' => $era ? ['value' => $era->value, 'label' => $era->label()] : null,
                'variants' => VariantResource::collection($groups->get($era?->value ?? 'unknown', collect())),
            ])
            ->filter(fn (array $group) => $group['variants']->count() > 0)
            ->values();

        return parent::toArray($request) + ['eras' => $eras];
    }
}
