<?php

namespace App\Services\Import;

use App\Services\BaseService;
use RuntimeException;

/**
 * Reads and normalizes the two collection export tabs.
 *
 * Parsing here is mechanical only: whitespace, casing, currency formatting and
 * the year ranges the owner embeds in color names. Semantic decisions, such as
 * whether "nappy bowl" and "Nappy 8.5\"" are the same product, are deliberately
 * left alone and surfaced in the import report instead.
 */
class SeedDataReader extends BaseService
{
    public const LINE_FIESTA = 'Fiesta';

    public const LINE_RIVIERA = 'Riviera';

    public const LINE_HARLEQUIN = 'Harlequin';

    /**
     * Spelling drift for the same color inside one line, approved by the owner.
     */
    private const COLOR_ALIASES = [
        'blue (cobalt)' => 'Cobalt',
        'green/original green' => 'Light Green',
        'old ivory' => 'Ivory',
        'grey' => 'Gray',
    ];

    /**
     * Words left lowercase inside a title cased name unless they lead it.
     */
    private const MINOR_WORDS = ['and', 'of', 'the', 'with', 'in', 'on', 'a'];

    /**
     * Longer color names used by the swatch list only.
     */
    private const HEX_NAME_ALIASES = [
        'cobalt blue' => 'Cobalt',
        'periwinkle blue' => 'Periwinkle',
        'sea mist green' => 'Sea Mist',
        'sky blue' => 'Sky',
    ];

    /**
     * Matrix column headers that map cleanly onto a detail-tab product name.
     * Columns absent from this map are categories rather than products and are
     * reported as unmapped rather than invented.
     */
    public const MATRIX_PRODUCT_MAP = [
        'Dinner Plate' => '10" plate',
        'Salad Plate' => '7" plate',
        'T&J Mug' => 'T&J mug',
        'Coffee Mug' => 'C-mug',
        '7" Cereal' => '7" bowl',
        'Saucer' => 'saucer',
        'Teacup' => 'teacup',
        'Carafe' => 'carafe',
        'Gravy Boat' => 'Gravy Boat',
        'Butter' => 'large butter',
    ];

    /**
     * Rows from the detail tab, one per spreadsheet line.
     *
     * @return array<int, array{row: int, color: string, line: string, type: string, qty: ?int, qty_raw: string, value: ?float}>
     */
    public function detailRows(): array
    {
        $rows = [];

        foreach ($this->read('collection-detail.csv', $header) as $number => $record) {
            $record = array_pad($record, 6, '');
            $color = trim($record[0]);

            if ($color === '') {
                continue;
            }

            $qtyRaw = trim($record[4]);

            $rows[] = [
                'row' => $number,
                'color' => $color,
                'line' => match (true) {
                    trim($record[1]) !== '' => self::LINE_RIVIERA,
                    trim($record[2]) !== '' => self::LINE_HARLEQUIN,
                    default => self::LINE_FIESTA,
                },
                'type' => trim($record[3]),
                'qty' => ctype_digit($qtyRaw) ? (int) $qtyRaw : null,
                'qty_raw' => $qtyRaw,
                'value' => $this->money($record[5]),
            ];
        }

        return $rows;
    }

    /**
     * Rows from the matrix tab. Cells are keyed by product column header.
     *
     * @return array<int, array{row: int, color: string, cells: array<string, string>}>
     */
    public function matrixRows(): array
    {
        $rows = [];
        $records = $this->read('collection-matrix.csv', $header);
        $columns = [];

        foreach ($header as $index => $name) {
            $name = trim($name);

            if ($index > 0 && $name !== '') {
                $columns[$index] = $name;
            }
        }

        foreach ($records as $number => $record) {
            $color = trim($record[0] ?? '');

            if ($color === '' || strtolower($color) === 'total') {
                continue;
            }

            $cells = [];

            foreach ($columns as $index => $name) {
                $cells[$name] = trim($record[$index] ?? '');
            }

            $rows[] = ['row' => $number, 'color' => $color, 'cells' => $cells];
        }

        return $rows;
    }

    /**
     * Community sourced swatch values, supplied by the owner.
     *
     * Columns are name, produced_from, produced_to, hex, group. The file has no
     * line column, so every row is read as Fiesta. Returns null when absent.
     *
     * @return ?array<int, array{row: int, name: string, produced_from: ?int, produced_to: ?int, hex: string, group: string}>
     */
    public function hexRows(): ?array
    {
        if (! is_readable(database_path('seed-data/color-hex.csv'))) {
            return null;
        }

        $rows = [];

        foreach ($this->read('color-hex.csv', $header) as $number => $record) {
            $record = array_pad($record, 5, '');

            if (trim($record[0]) === '') {
                continue;
            }

            $rows[] = [
                'row' => $number,
                'name' => trim($record[0]),
                'produced_from' => ctype_digit(trim($record[1])) ? (int) trim($record[1]) : null,
                'produced_to' => ctype_digit(trim($record[2])) ? (int) trim($record[2]) : null,
                'hex' => trim($record[3]),
                'group' => trim($record[4]),
            ];
        }

        return $rows;
    }

    /**
     * Applied decorations, supplied by the owner.
     *
     * `source_name` is the string as it appears in the collection export; the
     * remaining columns say what that string actually describes. Returns null
     * when no file is present.
     *
     * @return ?array<int, array{row: int, source_name: string, decoration: string, category: string, color: string, color_produced_from: ?int, produced_from: ?int, produced_to: ?int, notes: string}>
     */
    public function decorationRows(): ?array
    {
        if (! is_readable(database_path('seed-data/decorations.csv'))) {
            return null;
        }

        $rows = [];

        foreach ($this->read('decorations.csv', $header) as $number => $record) {
            $record = array_pad($record, 8, '');

            if (trim($record[0]) === '') {
                continue;
            }

            $rows[] = [
                'row' => $number,
                'source_name' => trim($record[0]),
                'decoration' => trim($record[1]),
                'category' => trim($record[2]),
                'color' => trim($record[3]),
                'color_produced_from' => ctype_digit(trim($record[4])) ? (int) trim($record[4]) : null,
                'produced_from' => ctype_digit(trim($record[5])) ? (int) trim($record[5]) : null,
                'produced_to' => ctype_digit(trim($record[6])) ? (int) trim($record[6]) : null,
                'notes' => trim($record[7]),
            ];
        }

        return $rows;
    }

    /**
     * The swatch list qualifies names the catalog does not, and uses a longer
     * form for four colors. Both are stripped so the row can be matched.
     */
    public static function parseHexName(string $raw): string
    {
        $name = trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', $raw));
        $name = preg_replace('/\s+/', ' ', $name);

        return self::properCase(self::HEX_NAME_ALIASES[mb_strtolower($name)] ?? $name);
    }

    /** @return array<int, string> */
    public function matrixColumns(): array
    {
        $this->read('collection-matrix.csv', $header);

        return array_values(array_filter(array_map('trim', array_slice($header, 1))));
    }

    /**
     * Split a color string such as "Cobalt(1936-1951)" or "Sunflower (2001-)"
     * into a name and a production range.
     *
     * A parenthetical is only treated as a date range when it contains a four
     * digit year, so "Red (orange red)" keeps its qualifier as part of the name.
     *
     * @return array{name: string, from: ?int, to: ?int}
     */
    public static function parseColor(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        $from = null;
        $to = null;
        $name = $raw;

        if (preg_match('/\(([^)]*\d{4}[^)]*)\)/', $raw, $matches)) {
            $inner = $matches[1];
            preg_match_all('/\d{4}/', $inner, $found);
            $years = array_map('intval', $found[0]);

            $from = $years[0];
            $to = match (true) {
                count($years) > 1 => $years[1],
                str_contains($inner, '-') => null,
                default => $years[0],
            };

            $name = str_replace($matches[0], '', $raw);
        }

        $name = trim(preg_replace('/\s+/', ' ', $name), " \t\n\r\0\x0B.");
        $name = self::COLOR_ALIASES[mb_strtolower($name)] ?? $name;

        return ['name' => self::properCase($name), 'from' => $from, 'to' => $to];
    }

    /**
     * Normalize a product string. Casing and whitespace only; no semantic merging.
     *
     * The key is lowercased so that rows differing only in case collapse onto
     * one product, while the display form is title cased for presentation.
     *
     * @return ?array{key: string, display: string}
     */
    public static function parseProduct(string $raw): ?array
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $raw));

        if ($trimmed === '') {
            return null;
        }

        // The key is derived from the display form so that punctuation tidied
        // during casing, such as the space after a comma, cannot make the
        // lookup key and the stored name disagree.
        $display = self::properCase($trimmed);

        return ['key' => mb_strtolower($display), 'display' => $display, 'raw' => $trimmed];
    }

    /**
     * Title case a name for display.
     *
     * The source spreadsheet is inconsistent about casing, so labels are
     * normalized once here rather than formatted at every call site. Tokens
     * carrying no lowercase letter are left alone, which keeps "T&J" and the
     * measurements in 8.5" intact.
     */
    public static function properCase(string $value): string
    {
        $value = preg_replace('/\s*,\s*/', ', ', trim($value));
        $position = 0;

        return preg_replace_callback('/[^\s\-\/,]+/u', function (array $match) use (&$position) {
            $word = $match[0];
            $index = $position++;

            if (! preg_match('/\p{Ll}/u', $word)) {
                return $word;
            }

            if ($index > 0 && in_array(mb_strtolower($word), self::MINOR_WORDS, true)) {
                return mb_strtolower($word);
            }

            return preg_replace_callback('/\p{L}/u', fn (array $letter) => mb_strtoupper($letter[0]), $word, 1);
        }, $value);
    }

    /**
     * Leading integer of a matrix cell. Cells such as "1 - ball" carry a
     * qualifier after the count; the owner's own SUM row skips these because
     * they are text, which is why her stated totals undercount.
     */
    public static function cellCount(string $cell): int
    {
        return preg_match('/^\s*(\d+)/', $cell, $matches) ? (int) $matches[1] : 0;
    }

    private function money(string $raw): ?float
    {
        $clean = str_replace(['$', ',', ' '], '', trim($raw));

        return $clean === '' ? null : (float) $clean;
    }

    /**
     * @param  array<int, string>  $header
     * @return array<int, array<int, string>>
     */
    private function read(string $file, ?array &$header = null): array
    {
        $path = database_path('seed-data/'.$file);

        if (! is_readable($path)) {
            throw new RuntimeException("Seed data file not readable: {$path}");
        }

        $handle = fopen($path, 'r');

        try {
            $header = fgetcsv($handle) ?: [];
            $rows = [];
            $number = 1;

            while (($record = fgetcsv($handle)) !== false) {
                $number++;

                if ($record === [null]) {
                    continue;
                }

                $rows[$number] = $record;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }
}
