<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * The single variant answer: the identification result, plus the pieces already
 * owned, the full value history behind the headline number, the listings that
 * evidence it exists, and the open wishlist item this piece would satisfy.
 */
class VariantDetailResource extends VariantResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'holdings' => HoldingResource::collection($this->whenLoaded('holdings')),
            // Only set for testers: how many of this the owner has.
            'owner' => $this->owner_count === null ? null : [
                'name' => $this->owner_name,
                'count' => (int) $this->owner_count,
            ],
            'value_history' => ValueObservationResource::collection($this->whenLoaded('valueHistory')),
            'evidence' => ExternalListingResource::collection(
                $this->whenLoaded('evidence', fn () => $this->evidence->pluck('listing')->sortByDesc('last_seen_at')->values())
            ),
            'wishlist_item' => $this->relationLoaded('wishlistMatch') && $this->wishlistMatch
                ? new WishlistItemResource($this->wishlistMatch)
                : null,
        ];
    }
}
