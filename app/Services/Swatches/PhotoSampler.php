<?php

namespace App\Services\Swatches;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;

/**
 * Estimates the glaze color in one product photo.
 *
 * The photo is read in memory and never stored. Only the middle of the frame is
 * looked at, where the piece sits, and the white studio background and deep
 * shadows are ignored. What is left is summarized as a per-channel median.
 */
class PhotoSampler extends BaseService
{
    private const WIDTH = 150;

    private const NEAR_WHITE = 235;

    private const SHADOW = 40;

    /** Below this share of the center counting as glaze, the photo is not trusted. */
    private const MIN_GLAZE_SHARE = 0.1;

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    public function sample(string $url): ?array
    {
        $response = Http::withUserAgent('FiestaFieldGuide/1.0 (+'.config('app.url').')')
            ->timeout(20)
            ->get($url, ['width' => 300]);

        if (! $response->successful()) {
            return null;
        }

        $image = @imagecreatefromstring($response->body());

        if ($image === false) {
            return null;
        }

        // Nearest neighbour keeps every pixel a real pixel from the photo. The
        // default smoothing blends colors and nudges them darker.
        return $this->glaze(imagescale($image, self::WIDTH, -1, IMG_NEAREST_NEIGHBOUR));
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function glaze(\GdImage $image): ?array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $channels = [[], [], []];
        $looked = 0;

        for ($x = (int) ($width * 0.2); $x < (int) ($width * 0.8); $x++) {
            for ($y = (int) ($height * 0.2); $y < (int) ($height * 0.8); $y++) {
                $looked++;
                $rgb = imagecolorat($image, $x, $y);
                $pixel = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];

                if (min($pixel) > self::NEAR_WHITE || max($pixel) < self::SHADOW) {
                    continue;
                }

                foreach ($pixel as $channel => $value) {
                    $channels[$channel][] = $value;
                }
            }
        }

        if ($looked === 0 || count($channels[0]) / $looked < self::MIN_GLAZE_SHARE) {
            return null;
        }

        return array_map(fn (array $values) => self::median($values), $channels);
    }

    /**
     * @param  array<int, int|float>  $values
     */
    public static function median(array $values): int
    {
        sort($values);

        return (int) round($values[intdiv(count($values), 2)]);
    }
}
