<?php

namespace App\Enums;

/**
 * Whose pieces a collection view counts: the person signed in, or the
 * admin's, which invited friends may look at but not change.
 */
enum CollectionView: string
{
    case Mine = 'mine';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Mine => 'Mine',
            self::Admin => "Admin's",
        };
    }
}
