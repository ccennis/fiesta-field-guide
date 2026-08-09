<?php

namespace App\Enums;

/**
 * Where a value observation came from. The important split is between
 * DefaultSchedule, a blanket per-product figure applied across many colors, and
 * everything else, which reflects real knowledge about a specific piece.
 */
enum ValueSource: string
{
    case DefaultSchedule = 'default_schedule';
    case OwnerEstimate = 'owner_estimate';
    case SoldComp = 'sold_comp';
    case PriceGuide = 'price_guide';

    public function label(): string
    {
        return match ($this) {
            self::DefaultSchedule => 'Default schedule',
            self::OwnerEstimate => 'Owner estimate',
            self::SoldComp => 'Sold comp',
            self::PriceGuide => 'Price guide',
        };
    }

    /**
     * True when the figure is a blanket placeholder rather than a considered
     * number for this piece.
     */
    public function isBlanket(): bool
    {
        return $this === self::DefaultSchedule;
    }
}
