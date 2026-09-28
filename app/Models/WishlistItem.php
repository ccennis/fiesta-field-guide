<?php

namespace App\Models;

use App\Enums\WishlistPriority;
use App\Enums\WishlistSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product_id', 'variant_id', 'priority', 'max_price', 'source', 'notes', 'fulfilled_at', 'fulfilled_by_holding_id'])]
class WishlistItem extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(Holding::class, 'fulfilled_by_holding_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('fulfilled_at');
    }

    public function isAnyColor(): bool
    {
        return $this->variant_id === null;
    }

    protected function casts(): array
    {
        return [
            'priority' => WishlistPriority::class,
            'source' => WishlistSource::class,
            'max_price' => 'decimal:2',
            'fulfilled_at' => 'datetime',
        ];
    }
}
