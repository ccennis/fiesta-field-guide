<?php

namespace App\Models;

use App\Enums\Condition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['variant_id', 'condition', 'condition_notes', 'purchase_price', 'purchase_date', 'notes'])]
class Holding extends Model
{
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    protected function casts(): array
    {
        return [
            'condition' => Condition::class,
            'purchase_price' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }
}
