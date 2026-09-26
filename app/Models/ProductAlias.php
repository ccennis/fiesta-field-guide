<?php

namespace App\Models;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['source', 'external_key', 'decision', 'product_id'])]
class ProductAlias extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'source' => ListingSource::class,
            'decision' => AliasDecision::class,
        ];
    }
}
