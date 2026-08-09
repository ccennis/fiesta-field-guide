<?php

namespace App\Models;

use App\Enums\Rarity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['line_id', 'name', 'rarity', 'notes'])]
class Product extends Model
{
    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function valueObservations(): HasMany
    {
        return $this->hasMany(ValueObservation::class);
    }

    protected function casts(): array
    {
        return [
            'rarity' => Rarity::class,
        ];
    }
}
