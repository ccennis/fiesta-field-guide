<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Line extends Model
{
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
