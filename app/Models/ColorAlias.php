<?php

namespace App\Models;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['source', 'external_key', 'decision', 'color_id'])]
class ColorAlias extends Model
{
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    protected function casts(): array
    {
        return [
            'source' => ListingSource::class,
            'decision' => AliasDecision::class,
        ];
    }
}
