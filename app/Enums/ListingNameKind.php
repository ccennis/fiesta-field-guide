<?php

namespace App\Enums;

/**
 * The two kinds of name a listing carries, each ruled on separately.
 */
enum ListingNameKind: string
{
    case Color = 'color';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Color => 'Color',
            self::Product => 'Product',
        };
    }

    /**
     * The listing column that holds this kind of name's lookup key.
     */
    public function keyColumn(): string
    {
        return $this->value.'_key';
    }
}
