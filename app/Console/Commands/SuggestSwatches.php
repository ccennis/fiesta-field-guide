<?php

namespace App\Console\Commands;

use App\Enums\ListingSource;
use App\Services\Import\ImportReport;
use App\Services\Swatches\SwatchSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Samples store photos of ruled colors and suggests swatches for the colors
 * that have none. Suggestions wait on the review screen for the owner.
 */
class SuggestSwatches extends Command
{
    protected $signature = 'fiesta:suggest-swatches';

    protected $description = 'Suggest swatches for colors without one, from store photos';

    public function handle(SwatchSuggestionService $swatches): int
    {
        $report = new ImportReport;
        $swatches->suggest(ListingSource::FiestaFactoryDirect, $report);

        $this->newLine();
        $this->info('Swatch suggestions');

        foreach ($report->counts() as $key => $value) {
            $this->line(sprintf('  %-42s %s', $key, $value));
        }

        foreach ($report->grouped() as $category => $messages) {
            $this->newLine();
            $this->warn('  '.$category.' ('.count($messages).')');

            foreach ($messages as $message) {
                $this->line('    - '.$message);
            }
        }

        Storage::disk('local')->put('reports/swatch-suggestions-report.txt', $report->toText('Swatch suggestions'));

        return self::SUCCESS;
    }
}
