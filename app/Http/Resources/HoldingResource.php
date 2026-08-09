<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HoldingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condition' => $this->condition
                ? ['value' => $this->condition->value, 'label' => $this->condition->label()]
                : null,
            'condition_notes' => $this->condition_notes,
            'purchase_price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'notes' => $this->notes,
            'variant' => new VariantResource($this->whenLoaded('variant')),
        ];
    }
}
