<?php

namespace App\Console\Commands;

use App\Enums\ListingSource;
use App\Models\ExternalListing;
use App\Services\Import\ImportReport;
use App\Services\Sources\ListingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ties store names that exactly match a catalog name and re-resolves the
 * store's listings, without fetching the store again. The weekly import does
 * the same after each fetch.
 */
class MatchStoreNames extends Command
{
    protected $signature = 'fiesta:match-store-names';

    protected $description = 'Tie store color and product names that exactly match the catalog';

    public function handle(ListingResolver $resolver): int
    {
        $source = ListingSource::FiestaFactoryDirect;

        if (! ExternalListing::where('source', $source)->exists()) {
            $this->line('  No store listings yet. Nothing to match.');

            return self::SUCCESS;
        }

        $report = new ImportReport;

        DB::transaction(fn () => $resolver->resolve($source, $report));

        foreach ($report->counts() as $key => $value) {
            $this->line(sprintf('  %-48s %s', $key, $value));
        }

        return self::SUCCESS;
    }
}
