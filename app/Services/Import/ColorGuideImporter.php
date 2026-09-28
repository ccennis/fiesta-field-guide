<?php

namespace App\Services\Import;

use App\Models\Color;
use App\Models\Line;
use App\Services\BaseService;
use App\Services\ColorService;
use Illuminate\Support\Collection;

/**
 * Applies fiesta-color-guide.com, the owner's chosen source for Fiesta swatches
 * and production years, to the catalog's Fiesta colors.
 *
 * Each row finds its color by name, and where a name recurs across eras, by the
 * nearest start year. That color takes the guide's swatch and years. A row with
 * no color in the catalog adds one. Runs after both imports, because the
 * spreadsheet finds its colors by the start years it was written with.
 */
class ColorGuideImporter extends BaseService
{
    public function __construct(
        private SeedDataReader $reader,
        private ColorService $colorService,
    ) {}

    public function apply(ImportReport $report): void
    {
        $rows = $this->reader->colorGuideRows();
        $fiesta = Line::where('name', SeedDataReader::LINE_FIESTA)->first();

        if ($rows === null || $fiesta === null) {
            $report->add('Color guide', 'No fiesta-color-guide.csv or no Fiesta line. Nothing applied.');

            return;
        }

        $colors = Color::where('line_id', $fiesta->id)->get();
        $taken = [];

        foreach ($rows as $row) {
            $color = $this->match($colors, $row, $taken);
            $data = [
                'hex' => mb_strtolower($row['hex']),
                'produced_from' => $row['produced_from'],
                'produced_to' => $row['produced_to'],
            ];

            if ($color === null) {
                $this->colorService->create(['line_id' => $fiesta->id, 'name' => $row['catalog_name']] + $data);
                $report->add('Colors added from the guide', "{$row['catalog_name']} ({$row['produced_from']}).");

                continue;
            }

            $taken[$color->id] = true;
            $before = $color->produced_label;
            $color->fill($data);

            if (! $color->isDirty()) {
                continue;
            }

            if ($color->isDirty(['produced_from', 'produced_to'])) {
                $report->add('Years changed to the guide\'s', "{$color->name}: {$before} became {$color->produced_label}.");
            }

            $report->count('colors updated from the guide');
            $color->save();
        }
    }

    /**
     * @param  Collection<int, Color>  $colors
     * @param  array{catalog_name: string, produced_from: int}  $row
     * @param  array<int, true>  $taken
     */
    private function match(Collection $colors, array $row, array $taken): ?Color
    {
        return $colors
            ->filter(fn (Color $color) => ! isset($taken[$color->id])
                && mb_strtolower($color->name) === mb_strtolower($row['catalog_name']))
            ->sortBy(fn (Color $color) => $color->produced_from === null ? PHP_INT_MAX : abs($color->produced_from - $row['produced_from']))
            ->first();
    }
}
