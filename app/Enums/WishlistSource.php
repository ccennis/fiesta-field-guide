<?php

namespace App\Enums;

/**
 * Where a wishlist item came from. Imported items are the qty 0 rows in the
 * collection export; everything added in the app is manual.
 */
enum WishlistSource: string
{
    case Import = 'import';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Import => 'Imported',
            self::Manual => 'Added in the app',
        };
    }
}
