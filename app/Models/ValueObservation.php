<?php

namespace App\Models;

use App\Enums\ValueSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'color_id', 'amount', 'source', 'observed_on', 'notes'])]
class ValueObservation extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'source' => ValueSource::class,
            'observed_on' => 'date',
        ];
    }
}
