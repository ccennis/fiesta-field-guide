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
            'is_admin' => $this->isAdmin(),
            'email_verified' => $this->hasVerifiedEmail(),
            // Invited friends see the admin's collection under the admin's name.
            // Null for everyone else, so the switch is not offered.
            'admin_name' => match (true) {
                $this->isAdmin() => $this->name,
                $this->canSeeAdminCollection() => User::admin()?->name,
                default => null,
            },
        ];
    }
}
