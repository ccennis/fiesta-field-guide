<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SwatchSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hex' => $this->hex,
            'photos_sampled' => $this->photos_sampled,
            'method' => $this->method,
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'color' => new ColorResource($this->whenLoaded('color')),
        ];
    }
}
