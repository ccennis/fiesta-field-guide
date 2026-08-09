<?php

namespace App\Models;

use App\Enums\DecorationCategory;
use App\Enums\Rarity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'produced_from', 'produced_to', 'rarity', 'notes'])]
class Decoration extends Model
{
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

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
            'category' => DecorationCategory::class,
            'rarity' => Rarity::class,
        ];
    }
}
