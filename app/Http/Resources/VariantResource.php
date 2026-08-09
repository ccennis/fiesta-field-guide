<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rarity is reported as separate facts. Nothing here combines color rarity and
 * product rarity into a score; the caller reads them side by side. A rarity set
 * directly on the variant overrides both.
 */
class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'existence' => [
                'value' => $this->existence->value,
                'label' => $this->existence->label(),
                'confirmed' => $this->existence->value === 'confirmed',
            ],
            'product' => new ProductResource($this->whenLoaded('product')),
            'color' => new ColorResource($this->whenLoaded('color')),
            'decoration' => new DecorationResource($this->whenLoaded('decoration')),
            'rarity' => [
                'override' => $this->rarity ? ['value' => $this->rarity->value, 'label' => $this->rarity->label()] : null,
                'color' => $this->whenLoaded('color', fn () => $this->color->rarity
                    ? ['value' => $this->color->rarity->value, 'label' => $this->color->rarity->label()]
                    : null),
                'product' => $this->whenLoaded('product', fn () => $this->product->rarity
                    ? ['value' => $this->product->rarity->value, 'label' => $this->product->rarity->label()]
                    : null),
                'decoration' => $this->whenLoaded('decoration', fn () => $this->decoration?->rarity
                    ? ['value' => $this->decoration->rarity->value, 'label' => $this->decoration->rarity->label()]
                    : null),
            ],
            'owned_count' => $this->whenCounted('holdings'),
            'value' => $this->relationLoaded('resolvedValue') && $this->resolvedValue
                ? new ValueObservationResource($this->resolvedValue)
                : null,
        ];
    }
}
