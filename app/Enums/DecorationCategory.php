<?php

namespace App\Enums;

/**
 * What kind of applied decoration this is.
 *
 * The vocabulary here uses only the owner's own words and is deliberately
 * short. It is nullable, and should be extended by the owner rather than
 * guessed at from outside the collection.
 */
enum DecorationCategory: string
{
    case Holiday = 'holiday';
    case Novelty = 'novelty';

    public function label(): string
    {
        return match ($this) {
            self::Holiday => 'Holiday',
            self::Novelty => 'Novelty',
        };
    }
}
