<?php

namespace App\Enums;

/**
 * Physical condition of a single held piece. Never applied as a multiplier to
 * value; it is displayed alongside the value estimate instead.
 */
enum Condition: string
{
    case Mint = 'mint';
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::Mint => 'Mint',
            self::Excellent => 'Excellent',
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Damaged => 'Damaged',
        };
    }
}
