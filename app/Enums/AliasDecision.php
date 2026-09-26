<?php

namespace App\Enums;

/**
 * The owner's ruling on a name an outside source uses. A mapped name points
 * at a catalog product or color; an ignored one is deliberately left out.
 */
enum AliasDecision: string
{
    case Mapped = 'mapped';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Mapped => 'Mapped',
            self::Ignored => 'Ignored',
        };
    }
}
