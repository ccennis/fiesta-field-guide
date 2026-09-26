<?php

namespace App\Console\Commands;

use App\Enums\ListingSource;
use App\Models\Color;
use App\Models\Product;
use App\Services\Import\ImportReport;
use App\Services\Sources\ListingResolver;
use App\Services\Sources\ListingRulingService;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Walks through the names a source uses that have no ruling yet, colors first
 * and then products, most listings first. Every answer is saved as it is
 * given, so stopping partway loses nothing.
 */
class RuleListings extends Command
{
    protected $signature = 'fiesta:rule-listings';

    protected $description = 'Rule on the color and product names used by imported listings';

    private const MAP = 'map';

    private const CREATE = 'create';

    private const IGNORE = 'ignore';

    private const LATER = 'later';

    private const STOP = 'stop';

    public function handle(ListingRulingService $rulings, ListingResolver $resolver): int
    {
        $source = ListingSource::FiestaFactoryDirect;

        $stopped = $this->ruleColors($rulings, $source) || $this->ruleProducts($rulings, $source);

        $report = new ImportReport;
        $resolver->resolve($source, $report);

        $this->newLine();
        info($stopped ? 'Stopped. Everything answered so far is saved.' : 'No names left to rule on.');

        foreach ($report->counts() as $key => $value) {
            $this->line(sprintf('  %-42s %s', $key, $value));
        }

        return self::SUCCESS;
    }

    /**
     * @return bool true when the owner chose to stop
     */
    private function ruleColors(ListingRulingService $rulings, ListingSource $source): bool
    {
        foreach ($rulings->pendingColors($source) as $pending) {
            $this->describe('Color', $pending);

            $choice = select('What is this color?', [
                self::MAP => 'One of my colors',
                self::CREATE => 'A color I need to add',
                self::IGNORE => 'Ignore it',
                self::LATER => 'Decide later',
                self::STOP => 'Stop for now',
            ]);

            match ($choice) {
                self::MAP => $rulings->mapColor($source, $pending->name_key, Color::findOrFail(search(
                    label: 'Which color? Names repeat across eras, so check the years.',
                    options: fn (string $value) => $rulings->colorChoices($value),
                ))),
                self::CREATE => $rulings->createColor(
                    $source,
                    $pending->name_key,
                    text('Color name', default: ucwords($pending->name), required: true),
                    $this->year('First year made'),
                    $this->year('Last year made (blank if still in production)'),
                ),
                self::IGNORE => $rulings->ignoreColor($source, $pending->name_key),
                default => null,
            };

            if ($choice === self::STOP) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool true when the owner chose to stop
     */
    private function ruleProducts(ListingRulingService $rulings, ListingSource $source): bool
    {
        foreach ($rulings->pendingProducts($source) as $pending) {
            $this->describe('Product', $pending);

            $choice = select('What is this piece?', [
                self::MAP => 'One of my products',
                self::CREATE => 'A product I need to add',
                self::IGNORE => 'Ignore it',
                self::LATER => 'Decide later',
                self::STOP => 'Stop for now',
            ]);

            match ($choice) {
                self::MAP => $this->mapProduct($rulings, $source, $pending->name_key, Product::findOrFail(search(
                    label: 'Which product?',
                    options: fn (string $value) => $rulings->productChoices($value),
                ))),
                self::CREATE => $rulings->createProduct(
                    $source,
                    $pending->name_key,
                    text('Product name', default: $pending->name, required: true),
                ),
                self::IGNORE => $rulings->ignoreProduct($source, $pending->name_key),
                default => null,
            };

            if ($choice === self::STOP) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mapping a store name onto a product gives the product the store's wording.
     */
    private function mapProduct(ListingRulingService $rulings, ListingSource $source, string $key, Product $product): void
    {
        $rulings->mapProduct($source, $key, $product);
        $rename = $rulings->adoptStoreName($source, $product);

        if ($rename['to'] !== null) {
            info("Renamed \"{$rename['from']}\" to \"{$rename['to']}\".");
        }

        if ($rename['conflict'] !== null) {
            $this->warn($rename['conflict']);
        }
    }

    private function describe(string $kind, object $pending): void
    {
        $this->newLine();
        info("{$kind}: \"{$pending->name}\" appears on {$pending->listings} ".($pending->listings === 1 ? 'listing' : 'listings'));
        $this->line("  e.g. {$pending->example}");
    }

    private function year(string $label): ?int
    {
        $value = text($label, validate: fn (string $value) => $value === '' || preg_match('/^(19|20)\d{2}$/', $value)
            ? null
            : 'Enter a four-digit year, or leave it blank.');

        return $value === '' ? null : (int) $value;
    }
}
