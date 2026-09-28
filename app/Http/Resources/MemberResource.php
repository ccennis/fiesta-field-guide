<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'pieces' => $this->whenCounted('holdings'),
            'joined' => $this->created_at?->toDateString(),
            'invited' => $this->sees_admin_collection,
            'email_verified' => $this->hasVerifiedEmail(),
            'disabled' => $this->isDisabled(),
        ];
    }
}
