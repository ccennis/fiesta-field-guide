<?php

namespace App\Enums;

/**
 * Rarity is recorded independently on colors, products and variants. Nothing
 * combines them into a score. A variant value, when set, overrides the color
 * and product readings for that specific combination.
 */
enum Rarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case VeryRare = 'very_rare';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Common',
            self::Uncommon => 'Uncommon',
            self::Rare => 'Rare',
            self::VeryRare => 'Very rare',
        };
    }
}
