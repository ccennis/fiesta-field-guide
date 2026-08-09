<?php

namespace App\Enums;

/**
 * Whether a product and color combination is known to have actually been made.
 *
 * The catalog is a cross product of colors and products within a line, so most
 * combinations are generated rather than verified. Only combinations evidenced
 * by the source data are marked Confirmed; the rest must never be presented as
 * real listings.
 */
enum VariantExistence: string
{
    case Confirmed = 'confirmed';
    case Unconfirmed = 'unconfirmed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Known example',
            self::Unconfirmed => 'No known example',
        };
    }
}
