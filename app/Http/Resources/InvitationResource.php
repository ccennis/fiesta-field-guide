<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The link itself is only known when the invitation is made, so it appears on
 * that one response and never in a listing.
 */
class InvitationResource extends JsonResource
{
    public function __construct($resource, private ?string $link = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'sent_to' => $this->sent_to,
            'expires_at' => $this->expires_at->toDateString(),
            'created_at' => $this->created_at->toDateString(),
            'link' => $this->when($this->link !== null, $this->link),
        ];
    }
}
