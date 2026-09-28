<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A color that matched a browse search, with every product it pairs with.
 */
class BrowseColorResource extends ColorResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
