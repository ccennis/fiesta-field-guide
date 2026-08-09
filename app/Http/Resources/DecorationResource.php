<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecorationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category
                ? ['value' => $this->category->value, 'label' => $this->category->label()]
                : null,
            'produced_label' => $this->produced_label,
            'rarity' => $this->rarity ? ['value' => $this->rarity->value, 'label' => $this->rarity->label()] : null,
            'notes' => $this->notes,
        ];
    }
}
