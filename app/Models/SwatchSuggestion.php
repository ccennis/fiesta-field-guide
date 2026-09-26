<?php

namespace App\Models;

use App\Enums\ListingSource;
use App\Enums\SuggestionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['color_id', 'source', 'hex', 'photos_sampled', 'method', 'status'])]
class SwatchSuggestion extends Model
{
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    protected function casts(): array
    {
        return [
            'source' => ListingSource::class,
            'status' => SuggestionStatus::class,
            'photos_sampled' => 'integer',
        ];
    }
}
