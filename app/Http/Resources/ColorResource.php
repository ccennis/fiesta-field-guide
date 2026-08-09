<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'produced_from' => $this->produced_from,
            'produced_to' => $this->produced_to,
            'produced_label' => $this->produced_label,
            'era' => $this->era ? ['value' => $this->era->value, 'label' => $this->era->label()] : null,
            'rarity' => $this->rarity ? ['value' => $this->rarity->value, 'label' => $this->rarity->label()] : null,
            'hex' => $this->hex,
            'line' => new LineResource($this->whenLoaded('line')),
        ];
    }
}
