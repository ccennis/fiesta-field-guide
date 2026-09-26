<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `value` is the resolved figure for the target: the variant's own when one is
 * named, otherwise the product level figure. `max_price` is the owner's ceiling
 * and is never mixed into it.
 */
class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'priority' => ['value' => $this->priority->value, 'label' => $this->priority->label()],
            'any_color' => $this->isAnyColor(),
            'max_price' => $this->max_price !== null ? (float) $this->max_price : null,
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'notes' => $this->notes,
            'fulfilled' => $this->fulfilled_at !== null,
            'fulfilled_at' => $this->fulfilled_at?->toDateString(),
            'fulfilled_by_holding_id' => $this->fulfilled_by_holding_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new VariantResource($this->whenLoaded('variant')),
            'value' => $this->relationLoaded('resolvedValue') && $this->resolvedValue
                ? new ValueObservationResource($this->resolvedValue)
                : null,
        ];
    }
}
