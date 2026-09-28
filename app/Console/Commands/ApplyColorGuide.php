<?php

namespace App\Console\Commands;

use App\Services\Import\ColorGuideImporter;
use App\Services\Import\ImportReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Takes Fiesta swatches and years from database/seed-data/fiesta-color-guide.csv.
 * Run it after fiesta:import-catalog and fiesta:import-holdings. It is safe to
 * run again.
 */
class ApplyColorGuide extends Command
{
    protected $signature = 'fiesta:apply-color-guide';

    protected $description = 'Apply fiesta-color-guide.com swatches and years to the Fiesta colors';

    public function handle(ColorGuideImporter $importer): int
    {
        $report = new ImportReport;

        DB::transaction(fn () => $importer->apply($report));

        foreach ($report->counts() as $key => $value) {
            $this->line(sprintf('  %-40s %s', $key, $value));
        }

        foreach ($report->grouped() as $category => $messages) {
            $this->newLine();
            $this->warn('  '.$category.' ('.count($messages).')');

            foreach ($messages as $message) {
                $this->line('    - '.$message);
            }
        }

        return self::SUCCESS;
    }
}
