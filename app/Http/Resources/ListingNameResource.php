<?php

namespace App\Http\Resources;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A name a source uses, the listings behind it, and the owner's ruling if one
 * has been made.
 */
class ListingNameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kind' => $this->kind->value,
            'key' => $this->key,
            'name' => $this->name,
            'listings' => $this->listings,
            'retired' => $this->retired,
            'resolved' => $this->resolved,
            'confirmed_now' => $this->confirmed_now ?? null,
            'examples' => ExternalListingResource::collection($this->examples),
            'colors' => $this->colors,
            'products' => $this->products,
            'rename' => $this->rename ?? null,
            'ruling' => $this->ruling === null ? null : [
                'decision' => ['value' => $this->ruling->decision->value, 'label' => $this->ruling->decision->label()],
                'target' => $this->target(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function target(): ?array
    {
        if ($this->ruling->decision !== AliasDecision::Mapped) {
            return null;
        }

        if ($this->kind === ListingNameKind::Color) {
            $color = $this->ruling->color;

            return ['id' => $color->id, 'name' => $color->name, 'label' => $color->produced_label, 'hex' => $color->hex];
        }

        return ['id' => $this->ruling->product->id, 'name' => $this->ruling->product->name, 'label' => null, 'hex' => null];
    }
}
