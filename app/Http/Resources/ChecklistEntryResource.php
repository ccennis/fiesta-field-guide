<?php

namespace App\Http\Resources;

use App\Enums\VariantExistence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One product on a color's "made in this color" checklist. `locked` says why a
 * verified piece cannot be unchecked: the owner has one, or a store listing
 * shows one.
 */
class ChecklistEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locked = match (true) {
            $this->holdings_count > 0 => 'You own one',
            $this->evidence_count > 0 => 'A store listing shows one',
            default => null,
        };

        return [
            'variant_id' => $this->id,
            'product' => new ProductResource($this->product),
            'made' => $this->existence === VariantExistence::Confirmed,
            'by_owner' => $this->confirmed_by_owner_at !== null,
            'locked' => $locked,
        ];
    }
}
