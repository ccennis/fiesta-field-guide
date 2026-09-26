<?php

namespace App\Console\Commands;

use App\Enums\ListingSource;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Services\Import\ImportReport;
use App\Services\Sources\FiestaFactoryDirectClient;
use App\Services\Sources\FiestaFactoryDirectParser;
use App\Services\Sources\ListingImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the Fiesta Factory Direct catalog into listings and applies the
 * owner's existing name rulings. New names are reported, never guessed at.
 */
class ImportFiestaFactoryDirect extends Command
{
    protected $signature = 'fiesta:import-ffd {--snapshot= : Re-parse a saved snapshot instead of fetching the store}';

    protected $description = 'Import listings from the Fiesta Factory Direct store';

    public function handle(FiestaFactoryDirectClient $client, FiestaFactoryDirectParser $parser, ListingImporter $importer): int
    {
        if (DB::table('variants')->count() === 0) {
            $this->error('No catalog found. Run fiesta:import-catalog first.');

            return self::FAILURE;
        }

        $snapshot = $this->option('snapshot');

        if ($snapshot) {
            $products = $client->fromSnapshot($snapshot);
        } else {
            ['products' => $products, 'snapshot' => $snapshot] = $client->fetch();
        }

        $knownColors = Color::pluck('name')
            ->merge(ColorAlias::where('source', ListingSource::FiestaFactoryDirect)->pluck('external_key'))
            ->all();

        $parsed = $parser->parse($products, $knownColors);
        $report = new ImportReport;
        $report->set('store products read', count($products));

        DB::transaction(fn () => $importer->import($parsed, ListingSource::FiestaFactoryDirect, now(), $report));

        $this->renderReport($report, 'Fiesta Factory Direct import', 'ffd-import-report.txt');
        $this->line('  Snapshot: storage/app/private/'.FiestaFactoryDirectClient::SNAPSHOT_DIR."/{$snapshot}");

        return self::SUCCESS;
    }

    private function renderReport(ImportReport $report, string $title, string $file): void
    {
        $this->newLine();
        $this->info($title);

        foreach ($report->counts() as $key => $value) {
            $this->line(sprintf('  %-58s %s', $key, $value));
        }

        foreach ($report->grouped() as $category => $messages) {
            $this->newLine();
            $this->warn('  '.$category.' ('.count($messages).')');

            foreach ($messages as $message) {
                $this->line('    - '.$message);
            }
        }

        Storage::disk('local')->put('reports/'.$file, $report->toText($title));

        $this->newLine();
        $this->line('  Report written to storage/app/private/reports/'.$file);
    }
}
