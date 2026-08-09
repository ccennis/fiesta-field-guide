<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValueObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'source' => [
                'value' => $this->source->value,
                'label' => $this->source->label(),
                'is_blanket' => $this->source->isBlanket(),
            ],
            'scope' => $this->color_id === null ? 'product' : 'variant',
            'observed_on' => $this->observed_on?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
