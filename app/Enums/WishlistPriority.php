<?php

namespace App\Enums;

enum WishlistPriority: string
{
    case Grail = 'grail';
    case Want = 'want';

    public function label(): string
    {
        return match ($this) {
            self::Grail => 'Grail',
            self::Want => 'Want',
        };
    }
}
