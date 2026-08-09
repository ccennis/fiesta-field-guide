<?php

namespace App\Console\Commands;

use App\Services\Import\CatalogImporter;
use App\Services\Import\ImportReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportCatalog extends Command
{
    protected $signature = 'fiesta:import-catalog {--fresh : Delete existing catalog rows first}';

    protected $description = 'Build lines, colors, products and the variant cross product from the seed data';

    public function handle(CatalogImporter $importer): int
    {
        if ($this->option('fresh')) {
            DB::table('holdings')->delete();
            DB::table('value_observations')->delete();
            DB::table('variants')->delete();
            DB::table('products')->delete();
            DB::table('colors')->delete();
            DB::table('lines')->delete();
        }

        $report = new ImportReport;

        DB::transaction(fn () => $importer->import($report));

        $this->renderReport($report, 'Catalog import', 'catalog-import-report.txt');

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
