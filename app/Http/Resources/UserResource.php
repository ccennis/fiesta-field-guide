<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => ['value' => $this->role->value, 'label' => $this->role->label()],
            'is_owner' => $this->isOwner(),
            // Testers see the owner's collection under the owner's name.
            'owner_name' => $this->isOwner() ? $this->name : User::owner()?->name,
        ];
    }
}
