<?php

namespace App\Enums;

/**
 * An outside catalog that listings are read from. Listings are claims, and
 * become catalog facts only through the owner's name rulings.
 */
enum ListingSource: string
{
    case FiestaFactoryDirect = 'fiesta_factory_direct';

    public function label(): string
    {
        return match ($this) {
            self::FiestaFactoryDirect => 'Fiesta Factory Direct',
        };
    }
}
