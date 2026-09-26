<?php

namespace App\Services\Sources;

use App\Enums\ListingSource;
use App\Models\ExternalListing;
use App\Services\BaseService;
use App\Services\Import\ImportReport;
use Carbon\CarbonInterface;

/**
 * Records what a source lists right now. Every listing seen is stamped, so a
 * listing that stops appearing is reported as dropped from the store rather
 * than deleted. A piece that was once listed was still made.
 */
class ListingImporter extends BaseService
{
    public function __construct(
        private ListingResolver $resolver,
    ) {}

    /**
     * @param  array{listings: array<int, array<string, mixed>>, skipped: array<string, array<int, string>>}  $parsed
     */
    public function import(array $parsed, ListingSource $source, CarbonInterface $seenAt, ImportReport $report): void
    {
        $new = 0;

        foreach ($parsed['listings'] as $row) {
            $listing = ExternalListing::firstOrNew([
                'source' => $source,
                'external_variant_id' => $row['external_variant_id'],
            ]);

            if (! $listing->exists) {
                $listing->first_seen_at = $seenAt;
                $new++;
            }

            $listing->fill($row + ['last_seen_at' => $seenAt])->save();
        }

        $report->set('listings read', count($parsed['listings']));
        $report->set('listings new this run', $new);

        foreach ($parsed['skipped'] as $reason => $titles) {
            $report->set('skipped: '.mb_strtolower($reason), count($titles));

            if ($reason === FiestaFactoryDirectParser::SKIP_TYPE) {
                continue;
            }

            foreach ($titles as $title) {
                $report->add("Skipped: {$reason}", "\"{$title}\"");
            }
        }

        $this->reportDropped($source, $seenAt, $report);
        $this->resolver->resolve($source, $report);
    }

    private function reportDropped(ListingSource $source, CarbonInterface $seenAt, ImportReport $report): void
    {
        $dropped = ExternalListing::where('source', $source)->where('last_seen_at', '<', $seenAt)->orderBy('title')->get();

        $report->set('listings no longer on the store', $dropped->count());

        foreach ($dropped as $listing) {
            $report->add(
                'No longer on the store',
                "\"{$listing->title}\" in {$listing->color_name}, last seen {$listing->last_seen_at->toDateString()}."
            );
        }
    }
}
