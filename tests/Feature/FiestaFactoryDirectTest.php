<?php

namespace Tests\Feature;

use App\Enums\ListingSource;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Models\ExternalListing;
use App\Models\Holding;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Variant;
use App\Models\VariantEvidence;
use App\Services\Import\ImportReport;
use App\Services\Sources\FiestaFactoryDirectParser;
use App\Services\Sources\ListingResolver;
use App\Services\Sources\ListingRulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * The fixture products below are invented in the storefront's format. None of
 * them are copied from the store.
 */
class FiestaFactoryDirectTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE = ListingSource::FiestaFactoryDirect;

    private Line $fiesta;

    private Color $turquoise;

    private Color $vintageCobalt;

    private Color $modernCobalt;

    private Product $heartPlate;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Sleep::fake();

        $this->fiesta = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $this->turquoise = Color::create(['line_id' => $this->fiesta->id, 'name' => 'Turquoise', 'produced_from' => 1988]);
        $this->vintageCobalt = Color::create(['line_id' => $this->fiesta->id, 'name' => 'Cobalt', 'produced_from' => 1936, 'produced_to' => 1951]);
        $this->modernCobalt = Color::create(['line_id' => $this->fiesta->id, 'name' => 'Cobalt', 'produced_from' => 1986]);
        Color::create(['line_id' => $this->fiesta->id, 'name' => 'Lavender', 'produced_from' => 2026]);
        Color::create(['line_id' => $this->fiesta->id, 'name' => 'Foundry', 'produced_from' => 2016]);
        $this->heartPlate = Product::create(['line_id' => $this->fiesta->id, 'name' => 'Heart Plate']);

        foreach ([$this->turquoise, $this->vintageCobalt, $this->modernCobalt] as $color) {
            Variant::create(['product_id' => $this->heartPlate->id, 'color_id' => $color->id, 'existence' => VariantExistence::Unconfirmed]);
        }
    }

    public function test_it_reads_colors_from_options_and_from_titles(): void
    {
        $parsed = app(FiestaFactoryDirectParser::class)->parse($this->products(), ['Turquoise', 'Cobalt', 'Lavender', 'Foundry']);
        $byTitle = collect($parsed['listings'])->groupBy('title');

        $heart = $byTitle['Retired Fiesta 9 Inch Heart Plate'];
        $this->assertSame(['turquoise', 'cobalt'], $heart->pluck('color_key')->all());
        $this->assertSame('9 Inch Heart Plate', $heart->first()['product_name']);
        $this->assertTrue($heart->first()['is_retired']);

        $prefixed = $byTitle['Lavender Fiesta 6 1/2 Inch Cereal Bowl 11 OZ']->sole();
        $this->assertSame('lavender', $prefixed['color_key']);
        $this->assertSame('6 1/2 Inch Cereal Bowl 11 OZ', $prefixed['product_name']);

        $suffixed = $byTitle['Classic Rim 7 1/4 Inch Salad Plate Foundry']->sole();
        $this->assertSame('foundry', $suffixed['color_key']);
        $this->assertSame('classic rim 7 1/4 inch salad plate', $suffixed['product_key']);
    }

    public function test_a_single_color_set_counts_and_sizes_normalize(): void
    {
        $parsed = app(FiestaFactoryDirectParser::class)->parse($this->products(), ['Turquoise']);
        $set = collect($parsed['listings'])->firstWhere('title', 'Set of 4 Classic Rim 10 1/2-Inch Dinner Plates Turquoise');

        $this->assertTrue($set['is_set']);
        $this->assertSame('turquoise', $set['color_key']);
        $this->assertSame('classic rim 10 1/2 inch dinner plates', $set['product_key']);
    }

    public function test_leftover_set_punctuation_and_a_repeated_color_are_removed(): void
    {
        $products = [
            $this->product(20, 'Classic Rim 9 Inch Luncheon Plate, Set of 4 Turquoise', 'plates'),
            $this->product(21, 'Retired Ivory Bistro Coupe 7 1/4 Inch Salad Plate', 'plates', ['Ivory']),
        ];

        $listings = collect(app(FiestaFactoryDirectParser::class)->parse($products, ['Turquoise', 'Ivory'])['listings']);

        $this->assertSame('Classic Rim 9 Inch Luncheon Plate', $listings[0]['product_name']);
        $this->assertSame('Bistro Coupe 7 1/4 Inch Salad Plate', $listings[1]['product_name']);
        $this->assertSame('ivory', $listings[1]['color_key']);
    }

    public function test_it_skips_with_a_reason_rather_than_guessing(): void
    {
        $skipped = app(FiestaFactoryDirectParser::class)->parse($this->products(), ['Turquoise', 'Lavender', 'Foundry'])['skipped'];

        $this->assertSame(['Tumbler Glass 16 OZ'], $skipped[FiestaFactoryDirectParser::SKIP_TYPE]);
        $this->assertSame(['Mixed Brights Set of 4 Mugs'], $skipped[FiestaFactoryDirectParser::SKIP_MIXED]);
        $this->assertSame(['Americana Band Salad Plate'], $skipped[FiestaFactoryDirectParser::SKIP_NO_COLOR]);
    }

    public function test_the_import_records_listings_and_leaves_new_names_pending(): void
    {
        $this->fakeStore($this->products());

        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $this->assertSame(6, ExternalListing::count());
        $this->assertSame(0, ExternalListing::whereNotNull('variant_id')->count());
        $this->assertSame(0, VariantEvidence::count());

        Http::assertSent(fn ($request) => str_starts_with($request->header('User-Agent')[0], 'FiestaFieldGuide/'));
        Storage::disk('local')->assertExists('reports/ffd-import-report.txt');
    }

    public function test_rulings_resolve_listings_into_evidence_and_confirm_variants(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $rulings = app(ListingRulingService::class);
        $rulings->mapProduct(self::SOURCE, '9 inch heart plate', $this->heartPlate);
        $rulings->mapColor(self::SOURCE, 'turquoise', $this->turquoise);
        $rulings->mapColor(self::SOURCE, 'cobalt', $this->modernCobalt);
        $this->resolve();

        $modern = $this->variant($this->modernCobalt);
        $this->assertSame(VariantExistence::Confirmed, $modern->existence);
        $this->assertSame(1, $modern->evidence()->count());
        $this->assertSame(VariantExistence::Confirmed, $this->variant($this->turquoise)->existence);

        // The owner ruled "cobalt" to mean the modern glaze, so vintage is untouched.
        $this->assertSame(VariantExistence::Unconfirmed, $this->variant($this->vintageCobalt)->existence);
    }

    public function test_changing_a_ruling_withdraws_evidence_unless_a_piece_is_owned(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $rulings = app(ListingRulingService::class);
        $rulings->mapProduct(self::SOURCE, '9 inch heart plate', $this->heartPlate);
        $rulings->mapColor(self::SOURCE, 'turquoise', $this->turquoise);
        $rulings->mapColor(self::SOURCE, 'cobalt', $this->modernCobalt);
        $this->resolve();

        Holding::create(['variant_id' => $this->variant($this->turquoise)->id]);

        $rulings->ignoreProduct(self::SOURCE, '9 inch heart plate');
        $this->resolve();

        $this->assertSame(0, VariantEvidence::count());
        $this->assertSame(VariantExistence::Unconfirmed, $this->variant($this->modernCobalt)->existence);
        $this->assertSame(VariantExistence::Confirmed, $this->variant($this->turquoise)->existence);
    }

    public function test_a_listing_that_disappears_is_reported_and_kept(): void
    {
        $this->fakeStore($this->products(), array_slice($this->products(), 1));
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $this->travel(7)->days();
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $this->assertSame(6, ExternalListing::count());
        $this->assertStringContainsString(
            'No longer on the store (2)',
            Storage::disk('local')->get('reports/ffd-import-report.txt')
        );
    }

    public function test_a_new_color_uses_only_what_the_owner_typed_and_joins_the_catalog(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $alias = app(ListingRulingService::class)->createColor(self::SOURCE, 'butterscotch', 'Butterscotch', 2020, null);

        $this->assertSame(2020, $alias->color->produced_from);
        $this->assertNull($alias->color->produced_to);
        $this->assertTrue(Variant::where('color_id', $alias->color_id)->where('product_id', $this->heartPlate->id)->exists());
    }

    public function test_names_that_exactly_match_are_tied_on_import_and_a_reused_color_goes_to_the_newest(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $tied = ColorAlias::pluck('color_id', 'external_key');

        $this->assertSame($this->modernCobalt->id, $tied['cobalt']);
        $this->assertSame($this->turquoise->id, $tied['turquoise']);
        $this->assertCount(4, $tied);
        $this->assertSame(0, app(ListingRulingService::class)->pendingColors(self::SOURCE)->count());
    }

    public function test_a_name_the_owner_already_ruled_on_is_not_retied(): void
    {
        app(ListingRulingService::class)->ignoreColor(self::SOURCE, 'cobalt');

        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $this->assertNull(ColorAlias::where('external_key', 'cobalt')->sole()->color_id);
    }

    public function test_pending_names_rank_by_how_many_listings_they_unlock(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        $pending = app(ListingRulingService::class)->pendingProducts(self::SOURCE);

        $this->assertSame('9 inch heart plate', $pending->first()->name_key);
        $this->assertSame(2, $pending->first()->listings);
        $this->assertSame(5, $pending->count());
    }

    public function test_the_rulings_command_saves_answers_as_it_goes_and_stops_cleanly(): void
    {
        $this->fakeStore($this->products());
        $this->artisan('fiesta:import-ffd')->assertSuccessful();

        // Every color in the fixture matches exactly, so only products are asked about.
        $options = [
            'map', 'One of my products',
            'create', 'A product I need to add',
            'ignore', 'Ignore it',
            'later', 'Decide later',
            'stop', 'Stop for now',
        ];

        $this->artisan('fiesta:rule-listings')
            ->expectsChoice('What is this piece?', 'ignore', $options)
            ->expectsChoice('What is this piece?', 'stop', $options)
            ->assertSuccessful();

        $this->assertSame(1, ProductAlias::count());
        $this->assertSame(4, app(ListingRulingService::class)->pendingProducts(self::SOURCE)->count());
    }

    private function resolve(): void
    {
        app(ListingResolver::class)->resolve(self::SOURCE, new ImportReport);
    }

    private function variant(Color $color): Variant
    {
        return Variant::where('product_id', $this->heartPlate->id)->where('color_id', $color->id)->sole();
    }

    /**
     * One catalog per import run, in order. Each run is a single page followed
     * by an empty one.
     *
     * @param  array<int, array<string, mixed>>  ...$runs
     */
    private function fakeStore(array ...$runs): void
    {
        $firstPages = Http::sequence();

        foreach ($runs as $products) {
            $firstPages->push(['products' => $products]);
        }

        Http::fake([
            'fiestafactorydirect.com/products.json?limit=250&page=1' => $firstPages,
            'fiestafactorydirect.com/products.json?limit=250&page=2' => Http::response(['products' => []]),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            $this->product(1, 'Retired Fiesta 9 Inch Heart Plate', 'plates', ['Turquoise', 'Cobalt']),
            $this->product(2, 'Lavender Fiesta 6 1/2 Inch Cereal Bowl 11 OZ', 'bowls'),
            $this->product(3, 'Classic Rim 7 1/4 Inch Salad Plate Foundry', 'plates'),
            $this->product(4, 'Set of 4 Classic Rim 10 1/2-Inch Dinner Plates Turquoise', 'plates'),
            $this->product(5, 'Tumbler Glass 16 OZ', 'Glassware'),
            $this->product(6, 'Mixed Brights Set of 4 Mugs', 'cups, mugs and saucers'),
            $this->product(7, 'Americana Band Salad Plate', 'plates'),
            $this->product(8, 'Lavender Mug 12 OZ', 'cups, mugs and saucers'),
        ];
    }

    /**
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function product(int $id, string $title, string $type, array $colors = []): array
    {
        $variants = $colors === []
            ? [['id' => $id * 100, 'option1' => 'Default Title']]
            : array_map(fn (string $color, int $i) => ['id' => $id * 100 + $i, 'option1' => $color], $colors, array_keys($colors));

        return [
            'id' => $id,
            'title' => $title,
            'handle' => 'product-'.$id,
            'product_type' => $type,
            'options' => [['name' => $colors === [] ? 'Title' : 'Color', 'position' => 1]],
            'variants' => $variants,
            'images' => [],
        ];
    }
}
