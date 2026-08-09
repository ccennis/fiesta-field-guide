<?php

namespace App\Services\Import;

use App\Enums\ValueSource;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Decoration;
use App\Models\Holding;
use App\Models\Line;
use App\Models\Product;
use App\Models\ValueObservation;
use App\Models\Variant;
use App\Services\BaseService;

/**
 * Imports owned pieces and value observations.
 *
 * The detail tab drives holdings because it is the only source carrying line,
 * value and real product names. The matrix tab contributes pieces for colors the
 * detail tab has no rows for at all. Quantities are expanded into one row per
 * physical object.
 *
 * The Value column is an extended figure, not a unit price: it sums to the
 * spreadsheet's own stated total, and every quantity above one divides cleanly
 * against the same product elsewhere in the sheet. Unit values are therefore
 * derived by division.
 */
class HoldingImporter extends BaseService
{
    /**
     * A per-product figure repeated at least this many times across different
     * colors is treated as a blanket schedule number rather than knowledge
     * about any particular piece.
     */
    private const SCHEDULE_THRESHOLD = 5;

    /** @var array<string, Color> */
    private array $colors = [];

    /** @var array<string, Product> */
    private array $products = [];

    /** @var array<string, Variant> */
    private array $variants = [];

    /** @var array<int, true> */
    private array $colorsSeenInDetail = [];

    /** @var array<int, Decoration> */
    private array $decorationIds = [];

    /** @var array<string, array{color_id: int, decoration_id: int}> */
    private array $decorationSources = [];

    /** @var array<int, array<int, array{product_id: int, color_id: int, amount: float}>> */
    private array $unitValues = [];

    public function __construct(
        private SeedDataReader $reader,
    ) {}

    public function import(ImportReport $report): void
    {
        $this->buildIndexes();
        $this->importDetail($report);
        $this->importMatrixOnlyColors($report);
        $this->buildValueObservations($report);

        $report->set('holdings created', Holding::count());
        $report->set('variants confirmed by evidence', Variant::where('existence', VariantExistence::Confirmed)->count());
        $report->set('value observations', ValueObservation::count());
    }

    private function buildIndexes(): void
    {
        foreach (Color::all() as $color) {
            $this->colors[$this->colorKey($color->line_id, $color->name, $color->produced_from)] = $color;
        }

        foreach (Product::all() as $product) {
            $this->products[$product->line_id.'|'.mb_strtolower($product->name)] = $product;
        }

        foreach (Variant::all() as $variant) {
            $this->variants[$variant->product_id.'|'.$variant->color_id.'|'.($variant->decoration_id ?? '')] = $variant;
        }

        foreach (Decoration::all() as $decoration) {
            $this->decorationIds[$decoration->id] = $decoration;
        }

        foreach ($this->reader->decorationRows() ?? [] as $row) {
            $decoration = Decoration::where('name', $row['decoration'])->first();

            if ($decoration === null) {
                continue;
            }

            $this->decorationSources[mb_strtolower($row['source_name'])] = [
                'color' => $row['color'],
                'color_produced_from' => $row['color_produced_from'],
                'decoration_id' => $decoration->id,
            ];
        }
    }

    private function importDetail(ImportReport $report): void
    {
        $lines = Line::pluck('id', 'name')->all();

        foreach ($this->reader->detailRows() as $row) {
            $lineId = $lines[$row['line']];
            $decorationSource = $this->decorationSources[mb_strtolower(SeedDataReader::parseColor($row['color'])['name'])] ?? null;

            if ($decorationSource !== null) {
                $parsed = ['name' => $decorationSource['color'], 'from' => $decorationSource['color_produced_from']];
                $from = $decorationSource['color_produced_from'];
            } else {
                $parsed = SeedDataReader::parseColor($row['color']);
                $from = $row['line'] === SeedDataReader::LINE_FIESTA ? $parsed['from'] : null;
            }

            $color = $this->colors[$this->colorKey($lineId, $parsed['name'], $from)] ?? null;

            if ($color === null) {
                $report->add('Rows skipped: color not resolved', "Row {$row['row']}: \"{$row['color']}\" ({$row['line']}).");

                continue;
            }

            $this->colorsSeenInDetail[$color->id] = true;

            $productParsed = SeedDataReader::parseProduct($row['type']);

            if ($productParsed === null) {
                $report->add(
                    'Rows skipped: no product given',
                    "Row {$row['row']}: \"{$row['color']}\" has a color but no Type, Qty or Value."
                );

                continue;
            }

            $product = $this->products[$lineId.'|'.$productParsed['key']] ?? null;
            $decorationId = $decorationSource['decoration_id'] ?? '';
            $variant = $product ? ($this->variants[$product->id.'|'.$color->id.'|'.$decorationId] ?? null) : null;

            if ($variant === null) {
                $report->add('Rows skipped: variant not resolved', "Row {$row['row']}: \"{$row['color']}\" / \"{$row['type']}\".");

                continue;
            }

            $variant->existence = VariantExistence::Confirmed;
            $variant->saveQuietly();

            $qty = $this->resolveQuantity($row, $report);

            if ($qty < 1) {
                if ($row['qty'] === 0) {
                    $report->count('detail rows listed but not owned (qty 0)');
                }

                continue;
            }

            if ($row['qty'] !== null && $row['qty'] > 1) {
                $report->add(
                    'Quantities above one, value divided',
                    "Row {$row['row']}: \"{$row['color']}\" / \"{$row['type']}\" qty {$row['qty']} at "
                    .'$'.number_format((float) $row['value'], 2).' becomes '.$row['qty'].' rows at $'
                    .number_format(((float) $row['value']) / $row['qty'], 2).' each.'
                );
            }

            for ($i = 0; $i < $qty; $i++) {
                Holding::create(['variant_id' => $variant->id]);
            }

            if ($row['value'] !== null) {
                $this->unitValues[$product->id][] = [
                    'product_id' => $product->id,
                    'color_id' => $color->id,
                    'amount' => round($row['value'] / $qty, 2),
                ];
            }
        }
    }

    /**
     * Blank quantities are ambiguous. A blank with a recorded value is read as
     * one piece, since the owner would not price something she does not have;
     * a blank with no value is read as none. Both are reported.
     */
    private function resolveQuantity(array $row, ImportReport $report): int
    {
        if ($row['qty'] !== null) {
            return $row['qty'];
        }

        if ($row['value'] !== null) {
            $report->add(
                'Blank quantities',
                "Row {$row['row']}: \"{$row['color']}\" / \"{$row['type']}\" has no quantity but is valued at $"
                .number_format($row['value'], 2).'. Imported as one piece.'
            );

            return 1;
        }

        $report->add(
            'Blank quantities',
            "Row {$row['row']}: \"{$row['color']}\" / \"{$row['type']}\" has no quantity and no value. Imported as none."
        );

        return 0;
    }

    /**
     * The matrix tab records pieces in colors the detail tab never mentions.
     * Only those colors are taken from it; everywhere else the detail tab wins.
     */
    private function importMatrixOnlyColors(ImportReport $report): void
    {
        $fiestaId = Line::where('name', SeedDataReader::LINE_FIESTA)->value('id');

        foreach ($this->reader->matrixRows() as $row) {
            $parsed = SeedDataReader::parseColor($row['color']);
            $color = $this->resolveMatrixColor($fiestaId, $parsed);

            if ($color === null || isset($this->colorsSeenInDetail[$color->id])) {
                continue;
            }

            foreach ($row['cells'] as $column => $cell) {
                $count = SeedDataReader::cellCount($cell);

                if ($count < 1) {
                    continue;
                }

                $productName = SeedDataReader::MATRIX_PRODUCT_MAP[$column] ?? null;
                $product = $productName ? ($this->products[$fiestaId.'|'.mb_strtolower($productName)] ?? null) : null;
                $variant = $product ? ($this->variants[$product->id.'|'.$color->id.'|'] ?? null) : null;

                if ($variant === null) {
                    $report->add(
                        'Matrix pieces not imported: column has no single product',
                        "\"{$row['color']}\" / \"{$column}\" ({$count}) - matrix row {$row['row']}."
                    );

                    continue;
                }

                $variant->existence = VariantExistence::Confirmed;
                $variant->saveQuietly();

                for ($i = 0; $i < $count; $i++) {
                    Holding::create(['variant_id' => $variant->id]);
                }

                $report->add(
                    'Pieces taken from the matrix tab only',
                    "\"{$row['color']}\" / \"{$column}\" x{$count} - the detail tab has no rows for this color."
                );
            }
        }
    }

    private function resolveMatrixColor(int $fiestaId, array $parsed): ?Color
    {
        if ($parsed['from'] !== null) {
            return $this->colors[$this->colorKey($fiestaId, $parsed['name'], $parsed['from'])] ?? null;
        }

        return Color::where('line_id', $fiestaId)
            ->where('name', $parsed['name'])
            ->orderByRaw('produced_from is null, produced_from')
            ->first();
    }

    /**
     * Splits the owner's blanket per-product figures from her considered numbers.
     *
     * A figure repeated across many colors of one product is stored once at product
     * level with a null color, so a single rough guess is not inflated into
     * hundreds of confident looking rows. Everything else is stored against the
     * specific variant and wins at read time.
     */
    private function buildValueObservations(ImportReport $report): void
    {
        $observedOn = now()->toDateString();
        $seen = [];

        foreach ($this->unitValues as $productId => $entries) {
            $frequencies = [];

            foreach ($entries as $entry) {
                $key = (string) $entry['amount'];
                $frequencies[$key] = ($frequencies[$key] ?? 0) + 1;
            }

            arsort($frequencies);
            $modal = (float) array_key_first($frequencies);
            $modalCount = reset($frequencies);
            $isSchedule = $modalCount >= self::SCHEDULE_THRESHOLD;

            if ($isSchedule) {
                ValueObservation::create([
                    'product_id' => $productId,
                    'color_id' => null,
                    'amount' => $modal,
                    'source' => ValueSource::DefaultSchedule,
                    'observed_on' => $observedOn,
                    'notes' => "Blanket figure applied across {$modalCount} colors in the source spreadsheet.",
                ]);
            }

            foreach ($entries as $entry) {
                if ($isSchedule && $entry['amount'] === $modal) {
                    continue;
                }

                $key = $entry['product_id'].'|'.$entry['color_id'];

                if (isset($seen[$key])) {
                    $report->add(
                        'Duplicate rows for the same product and color',
                        'Product '.$entry['product_id'].' color '.$entry['color_id']
                        .': a second value of $'.number_format($entry['amount'], 2).' was ignored.'
                    );

                    continue;
                }

                $seen[$key] = true;

                ValueObservation::create([
                    'product_id' => $entry['product_id'],
                    'color_id' => $entry['color_id'],
                    'amount' => $entry['amount'],
                    'source' => ValueSource::OwnerEstimate,
                    'observed_on' => $observedOn,
                ]);
            }
        }

        $report->add(
            'Dates',
            "The source spreadsheet carries no dates. Every imported observation is dated {$observedOn}, "
            .'the date of import, so value over time has a single point until you add more.'
        );
    }

    private function colorKey(int $lineId, string $name, ?int $from): string
    {
        return $lineId.'|'.mb_strtolower($name).'|'.($from ?? 'null');
    }
}
