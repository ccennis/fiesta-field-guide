<?php

namespace App\Enums;

/**
 * Where a derived suggestion stands. Only an accepted suggestion ever changes
 * catalog data.
 */
enum SuggestionStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting review',
            self::Accepted => 'Accepted',
            self::Dismissed => 'Dismissed',
        };
    }
}
