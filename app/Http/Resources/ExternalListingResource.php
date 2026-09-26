<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One store listing as cited evidence. The image is a link to the store's own
 * file and is never copied.
 */
class ExternalListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'title' => $this->title,
            'color_name' => $this->color_name,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'is_retired' => $this->is_retired,
            'is_set' => $this->is_set,
            'first_seen_at' => $this->first_seen_at?->toDateString(),
            'last_seen_at' => $this->last_seen_at?->toDateString(),
        ];
    }
}
