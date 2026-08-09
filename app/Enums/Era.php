<?php

namespace App\Enums;

/**
 * Production era, derived from a color's start year rather than stored.
 *
 * Boundaries are supplied by the collection owner: vintage runs 1936-1973,
 * post-86 runs 1986 onward. No color in the source data starts between those
 * ranges, so the gap is intentional and currently unpopulated.
 */
enum Era: string
{
    case Vintage = 'vintage';
    case Post86 = 'post_86';

    public const VINTAGE_START = 1936;

    public const VINTAGE_END = 1973;

    public const POST_86_START = 1986;

    /**
     * Resolve an era from a production start year. Returns null when the year
     * is unknown or falls outside both ranges.
     */
    public static function fromYear(?int $year): ?self
    {
        if ($year === null) {
            return null;
        }

        if ($year >= self::VINTAGE_START && $year <= self::VINTAGE_END) {
            return self::Vintage;
        }

        if ($year >= self::POST_86_START) {
            return self::Post86;
        }

        return null;
    }

    /**
     * Inclusive year bounds for this era, for use in queries.
     *
     * @return array{0: int, 1: int|null}
     */
    public function yearRange(): array
    {
        return match ($this) {
            self::Vintage => [self::VINTAGE_START, self::VINTAGE_END],
            self::Post86 => [self::POST_86_START, null],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Vintage => 'Vintage',
            self::Post86 => 'Post-86',
        };
    }
}
