<?php

namespace App\Models;

use App\Enums\ListingSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source', 'external_product_id', 'external_variant_id', 'title',
    'product_name', 'product_key', 'color_name', 'color_key',
    'is_retired', 'is_set', 'url', 'image_url', 'variant_id',
    'first_seen_at', 'last_seen_at',
])]
class ExternalListing extends Model
{
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    protected function casts(): array
    {
        return [
            'source' => ListingSource::class,
            'is_retired' => 'boolean',
            'is_set' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
