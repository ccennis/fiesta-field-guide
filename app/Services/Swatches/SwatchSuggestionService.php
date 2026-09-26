<?php

namespace App\Services\Swatches;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use App\Enums\SuggestionStatus;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Models\ExternalListing;
use App\Models\SwatchSuggestion;
use App\Services\BaseService;
use App\Services\ColorService;
use App\Services\Import\ImportReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Sleep;

/**
 * Suggests swatches for colors that have none, from a source's photos.
 *
 * Only colors the owner has ruled on are sampled, since the ruling is what says
 * which catalog color a store name means. The suggestion is the plain median
 * across several photos. Correcting it against existing swatches was tried and
 * made it worse, because several existing swatches are generic palette colors
 * rather than measurements. Colors that already have a swatch are still
 * sampled, so each suggestion can say how far photos and swatches tend to sit
 * apart. A suggestion never changes a color until the owner accepts it, and one
 * already accepted or dismissed is left alone.
 */
class SwatchSuggestionService extends BaseService
{
    /** Photos sampled per color. */
    private const PHOTOS = 5;

    public function __construct(
        private PhotoSampler $sampler,
        private ColorService $colorService,
    ) {}

    public function suggest(ListingSource $source, ImportReport $report): void
    {
        $distances = [];
        $targets = [];

        foreach ($this->ruledColors($source) as $colorId => $keys) {
            $color = Color::find($colorId);
            $sample = $this->sampleColor($source, $keys);

            if ($sample === null) {
                $report->add('Colors with no readable photos', "{$color->name} ({$color->produced_label}).");

                continue;
            }

            if ($color->hex !== null) {
                $distances[] = ColorMath::distance($sample['rgb'], ColorMath::fromHex($color->hex));
            } else {
                $targets[] = ['color' => $color, 'rgb' => $sample['rgb'], 'photos' => $sample['photos']];
            }
        }

        $method = $this->describe($distances);

        $report->set('colors with a swatch compared', count($distances));
        $report->set('colors without a swatch sampled', count($targets));

        foreach ($targets as $target) {
            $this->record($source, $target, ColorMath::toHex($target['rgb']), $method, $report);
        }
    }

    /**
     * @return Collection<int, SwatchSuggestion>
     */
    public function pending(): Collection
    {
        return SwatchSuggestion::with('color.line')
            ->where('status', SuggestionStatus::Pending)
            ->orderBy('created_at')
            ->get();
    }

    public function accept(SwatchSuggestion $suggestion): SwatchSuggestion
    {
        $this->colorService->update($suggestion->color, ['hex' => $suggestion->hex]);
        $suggestion->update(['status' => SuggestionStatus::Accepted]);

        return $suggestion->fresh('color.line');
    }

    public function dismiss(SwatchSuggestion $suggestion): SwatchSuggestion
    {
        $suggestion->update(['status' => SuggestionStatus::Dismissed]);

        return $suggestion->fresh('color.line');
    }

    /**
     * @return array<int, array<int, string>> store color keys, grouped by catalog color id
     */
    private function ruledColors(ListingSource $source): array
    {
        return ColorAlias::where('source', $source)
            ->where('decision', AliasDecision::Mapped)
            ->get()
            ->groupBy('color_id')
            ->map(fn ($aliases) => $aliases->pluck('external_key')->all())
            ->all();
    }

    /**
     * The per-channel median across a handful of photos. Single-piece listings
     * come first, since set photos crowd several pieces into the frame.
     *
     * @param  array<int, string>  $keys
     * @return array{rgb: array<int, int>, photos: int}|null
     */
    private function sampleColor(ListingSource $source, array $keys): ?array
    {
        $urls = ExternalListing::where('source', $source)
            ->whereIn('color_key', $keys)
            ->whereNotNull('image_url')
            ->orderBy('is_set')
            ->orderBy('id')
            ->pluck('image_url')
            ->unique()
            ->take(self::PHOTOS);

        $samples = [];

        foreach ($urls as $url) {
            Sleep::for(250)->milliseconds();
            $rgb = $this->sampler->sample($url);

            if ($rgb !== null) {
                $samples[] = $rgb;
            }
        }

        if ($samples === []) {
            return null;
        }

        return [
            'rgb' => array_map(fn (int $c) => PhotoSampler::median(array_column($samples, $c)), [0, 1, 2]),
            'photos' => count($samples),
        ];
    }

    /**
     * @param  array{color: Color, rgb: array<int, int>, photos: int}  $target
     */
    private function record(ListingSource $source, array $target, string $hex, string $method, ImportReport $report): void
    {
        $suggestion = SwatchSuggestion::firstOrNew(['color_id' => $target['color']->id, 'source' => $source]);

        if ($suggestion->exists && $suggestion->status !== SuggestionStatus::Pending) {
            $report->add('Already reviewed, left alone', "{$target['color']->name}: {$suggestion->status->label()} {$suggestion->hex}.");

            return;
        }

        $suggestion->fill([
            'hex' => $hex,
            'photos_sampled' => $target['photos'],
            'method' => $method,
            'status' => SuggestionStatus::Pending,
        ])->save();

        $report->add('Swatches suggested', "{$target['color']->name} ({$target['color']->produced_label}): {$hex} from {$target['photos']} photos.");
    }

    /**
     * @param  array<int, float>  $distances
     */
    private function describe(array $distances): string
    {
        $method = "Median color across the store's photos of this glaze, with the white background and deep shadows left out.";

        if ($distances === []) {
            return $method;
        }

        $average = round(array_sum($distances) / count($distances));
        $count = count($distances);

        return "{$method} For the {$count} ruled colors that already have a swatch, the photos sat {$average} apart from "
            .'those swatches on average, on a 0 to 441 scale. Several existing swatches are generic palette colors, so '
            .'that gap is partly theirs.';
    }
}
