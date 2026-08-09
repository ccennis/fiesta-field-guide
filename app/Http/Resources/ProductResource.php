<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rarity' => $this->rarity ? ['value' => $this->rarity->value, 'label' => $this->rarity->label()] : null,
            'notes' => $this->notes,
            'variants_count' => $this->whenCounted('variants'),
            'pieces_count' => $this->whenHas('pieces_count', fn () => (int) $this->pieces_count),
            'line' => new LineResource($this->whenLoaded('line')),
        ];
    }
}
