<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'disabled_at', 'sees_admin_collection'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The one account that keeps the catalog, or null before it exists.
     */
    public static function admin(): ?self
    {
        return static::where('role', UserRole::Admin)->orderBy('id')->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Friends who joined by invite may look at the admin's collection. People
     * who signed up on their own may not.
     */
    public function canSeeAdminCollection(): bool
    {
        return $this->isAdmin() || $this->sees_admin_collection;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'sees_admin_collection' => 'boolean',
        ];
    }
}
