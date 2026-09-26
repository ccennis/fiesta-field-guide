<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['variant_id', 'external_listing_id'])]
class VariantEvidence extends Model
{
    protected $table = 'variant_evidence';

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ExternalListing::class, 'external_listing_id');
    }
}
