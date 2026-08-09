<?php

namespace App\Models;

use App\Enums\Era;
use App\Enums\Rarity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['line_id', 'name', 'produced_from', 'produced_to', 'rarity', 'hex', 'notes'])]
class Color extends Model
{
    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * Era is never stored. It is read off the production start year so that the
     * two facts can never drift apart.
     */
    protected function era(): Attribute
    {
        return Attribute::get(fn (): ?Era => Era::fromYear($this->produced_from));
    }

    /**
     * The production range as it reads on a label, e.g. "1936-1951" or "1986-".
     */
    protected function producedLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->produced_from === null) {
                return null;
            }

            return $this->produced_to === null
                ? "{$this->produced_from}-"
                : "{$this->produced_from}-{$this->produced_to}";
        });
    }

    protected function casts(): array
    {
        return [
            'produced_from' => 'integer',
            'produced_to' => 'integer',
            'rarity' => Rarity::class,
        ];
    }
}
