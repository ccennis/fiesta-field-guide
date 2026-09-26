<?php

namespace App\Services\Swatches;

/**
 * Conversions and distance for swatch colors.
 */
class ColorMath
{
    /**
     * @return array<int, int>
     */
    public static function fromHex(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }

    /**
     * @param  array<int, int>  $rgb
     */
    public static function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...$rgb);
    }

    /**
     * Straight-line distance between two colors, from 0 for identical to 441
     * for black against white.
     *
     * @param  array<int, int>  $a
     * @param  array<int, int>  $b
     */
    public static function distance(array $a, array $b): float
    {
        return sqrt(array_sum(array_map(fn (int $c) => ($a[$c] - $b[$c]) ** 2, [0, 1, 2])));
    }
}
