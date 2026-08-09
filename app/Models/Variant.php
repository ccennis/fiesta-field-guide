<?php

namespace App\Models;

use App\Enums\Rarity;
use App\Enums\VariantExistence;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'color_id', 'decoration_id', 'existence', 'rarity', 'notes'])]
class Variant extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function decoration(): BelongsTo
    {
        return $this->belongsTo(Decoration::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    protected function casts(): array
    {
        return [
            'existence' => VariantExistence::class,
            'rarity' => Rarity::class,
        ];
    }
}
