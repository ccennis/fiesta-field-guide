<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * The single variant answer: the identification result, plus the pieces already
 * owned, the full value history behind the headline number, and the open
 * wishlist item this piece would satisfy.
 */
class VariantDetailResource extends VariantResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'holdings' => HoldingResource::collection($this->whenLoaded('holdings')),
            'value_history' => ValueObservationResource::collection($this->whenLoaded('valueHistory')),
            'wishlist_item' => $this->relationLoaded('wishlistMatch') && $this->wishlistMatch
                ? new WishlistItemResource($this->wishlistMatch)
                : null,
        ];
    }
}
