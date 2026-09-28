<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Import\HoldingImporter;
use App\Services\Import\ImportReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportHoldings extends Command
{
    protected $signature = 'fiesta:import-holdings {--fresh : Delete existing holdings, observations and wishlist items first}';

    protected $description = 'Import owned pieces, value observations and wishlist items from the seed data';

    public function handle(HoldingImporter $importer): int
    {
        if (DB::table('variants')->count() === 0) {
            $this->error('No catalog found. Run fiesta:import-catalog first.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            // Only the owner's rows come from the spreadsheet; testers' are left alone.
            $ownerId = User::owner()?->id;

            foreach (['wishlist_items', 'holdings'] as $table) {
                DB::table($table)
                    ->when($ownerId, fn ($query, $id) => $query->where('user_id', $id), fn ($query) => $query->whereNull('user_id'))
                    ->delete();
            }

            DB::table('value_observations')->delete();
        }

        $report = new ImportReport;

        DB::transaction(fn () => $importer->import($report));

        $this->renderReport($report, 'Holdings import', 'holdings-import-report.txt');

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
