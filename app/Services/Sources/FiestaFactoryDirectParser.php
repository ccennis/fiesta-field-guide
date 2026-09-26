<?php

namespace App\Services\Sources;

use App\Services\BaseService;

/**
 * Reads the Fiesta Factory Direct storefront catalog into one listing per
 * product and color.
 *
 * The store lists colors two ways: as a Color option on one product, or baked
 * into the title of a single listing ("Lavender Fiesta 6 1/2 Inch Cereal Bowl",
 * "Classic Rim 7 1/4 Inch Salad Plate Foundry"). Only a color found at the very
 * start or end of a title is accepted. Anything else is skipped with a reason,
 * never guessed at.
 */
class FiestaFactoryDirectParser extends BaseService
{
    public const STORE_URL = 'https://fiestafactorydirect.com';

    /** Store product types that are dinnerware or serveware. */
    private const INCLUDED_TYPES = [
        'plates',
        'bowls',
        'serveware',
        'cups, mugs and saucers',
        'bakeware',
        'pitchers, carafes and teapots',
        'discontinued',
    ];

    /** Listings that bundle several colors or pieces and so say nothing about one piece. */
    private const MIXED_PATTERN = '/\b(mixed|assorted|place settings?|\d+[- ]piece|piece set)\b/i';

    private const SET_PATTERN = '/\bset of \d+\b/i';

    private const RETIRED_PATTERN = '/^retired\b/i';

    public const SKIP_TYPE = 'Not dinnerware or serveware';

    public const SKIP_MIXED = 'Mixed set or place setting';

    public const SKIP_NO_COLOR = 'No color found in the title';

    /**
     * @param  array<int, array<string, mixed>>  $products  decoded storefront products
     * @param  array<int, string>  $knownColors  color names that may appear inside a title
     * @return array{listings: array<int, array<string, mixed>>, skipped: array<string, array<int, string>>}
     */
    public function parse(array $products, array $knownColors): array
    {
        $vocabulary = $this->vocabulary($products, $knownColors);
        $listings = [];
        $skipped = [];

        foreach ($products as $product) {
            $title = trim((string) $product['title']);

            if (! in_array(mb_strtolower(trim((string) ($product['product_type'] ?? ''))), self::INCLUDED_TYPES, true)) {
                $skipped[self::SKIP_TYPE][] = $title;

                continue;
            }

            if (preg_match(self::MIXED_PATTERN, $title)) {
                $skipped[self::SKIP_MIXED][] = $title;

                continue;
            }

            $retired = (bool) preg_match(self::RETIRED_PATTERN, $title);
            $isSet = (bool) preg_match(self::SET_PATTERN, $title);
            $name = $this->clean(preg_replace([self::RETIRED_PATTERN, self::SET_PATTERN], '', $title));
            $optionPosition = $this->colorOptionPosition($product);

            if ($optionPosition !== null) {
                // Some option listings still name a color in the title ("Ivory Bistro Coupe ...").
                [$titleColor, $rest] = $this->splitColor($name, $vocabulary);
                $name = $titleColor === null ? $name : $rest;

                foreach ($product['variants'] as $variant) {
                    $color = trim((string) $variant['option'.$optionPosition]);
                    $image = $variant['featured_image']['src'] ?? null;
                    $listings[] = $this->listing($product, $variant, $title, $name, $color, $retired, $isSet, $image);
                }

                continue;
            }

            [$color, $rest] = $this->splitColor($name, $vocabulary);

            if ($color === null) {
                $skipped[self::SKIP_NO_COLOR][] = $title;

                continue;
            }

            $image = $product['variants'][0]['featured_image']['src'] ?? $product['images'][0]['src'] ?? null;
            $listings[] = $this->listing($product, $product['variants'][0], $title, $rest, $color, $retired, $isSet, $image);
        }

        return ['listings' => $listings, 'skipped' => $skipped];
    }

    /**
     * The lookup key a ruling is stored against. Case, the brand name, the
     * trademark sign and spacing around sizes all vary between listings of the
     * same piece, so none of them count.
     */
    public static function key(string $name): string
    {
        $key = mb_strtolower($name);
        $key = str_replace('®', '', $key);
        $key = preg_replace('/\bfiesta\b/', ' ', $key);
        $key = preg_replace('/(\d)-inch\b/', '$1 inch', $key);
        $key = preg_replace('/(\d)\s*oz\b/', '$1 oz', $key);

        return trim(preg_replace('/\s+/', ' ', $key), ' ,;-');
    }

    /**
     * The image must show this color. On a product with a Color option, the
     * product's main photo shows only one of its colors, so only a photo tied
     * to the variant itself is kept.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $variant
     * @return array<string, mixed>
     */
    private function listing(array $product, array $variant, string $title, string $name, string $color, bool $retired, bool $isSet, ?string $image): array
    {
        return [
            'external_product_id' => (string) $product['id'],
            'external_variant_id' => (string) $variant['id'],
            'title' => $title,
            'product_name' => $name,
            'product_key' => self::key($name),
            'color_name' => $color,
            'color_key' => self::key($color),
            'is_retired' => $retired,
            'is_set' => $isSet,
            'url' => self::STORE_URL.'/products/'.$product['handle'].'?variant='.$variant['id'],
            'image_url' => $image,
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function colorOptionPosition(array $product): ?int
    {
        foreach ($product['options'] ?? [] as $index => $option) {
            if (mb_strtolower((string) $option['name']) === 'color') {
                return (int) ($option['position'] ?? $index + 1);
            }
        }

        return null;
    }

    /**
     * Find a known color at the start or end of a name, longest name first so
     * "Cobalt Blue" wins over "Cobalt".
     *
     * @param  array<int, string>  $vocabulary
     * @return array{0: ?string, 1: string}
     */
    private function splitColor(string $name, array $vocabulary): array
    {
        foreach ($vocabulary as $color) {
            $quoted = preg_quote($color, '/');

            if (preg_match('/^'.$quoted.'\b\s*(.+)$/iu', $name, $match)) {
                return [mb_substr($name, 0, mb_strlen($color)), $this->clean($match[1])];
            }

            if (preg_match('/^(.+?)\s*\b'.$quoted.'$/iu', $name, $match)) {
                return [mb_substr($name, -mb_strlen($color)), $this->clean($match[1])];
            }
        }

        return [null, $name];
    }

    /**
     * Known colors plus every color the store itself offers as an option,
     * longest first.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, string>  $knownColors
     * @return array<int, string>
     */
    private function vocabulary(array $products, array $knownColors): array
    {
        $names = array_map(fn (string $color) => mb_strtolower(trim($color)), $knownColors);

        foreach ($products as $product) {
            $position = $this->colorOptionPosition($product);

            if ($position === null) {
                continue;
            }

            foreach ($product['variants'] as $variant) {
                $names[] = mb_strtolower(trim((string) $variant['option'.$position]));
            }
        }

        $names = array_values(array_unique(array_filter($names)));
        usort($names, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return $names;
    }

    /**
     * Drop the brand name where it leads a title, the punctuation left behind
     * when "Set of 4" is removed, and extra spacing.
     */
    private function clean(string $name): string
    {
        $name = preg_replace('/^fiesta®?\s+/iu', '', trim($name));

        return trim(preg_replace('/\s+/', ' ', $name), ' ,;-');
    }
}
