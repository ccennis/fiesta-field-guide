<?php

namespace App\Enums;

/**
 * Whose pieces a collection view counts: the person signed in, or the owner's,
 * which testers may look at but not change.
 */
enum CollectionView: string
{
    case Mine = 'mine';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::Mine => 'Mine',
            self::Owner => "Owner's",
        };
    }
}
