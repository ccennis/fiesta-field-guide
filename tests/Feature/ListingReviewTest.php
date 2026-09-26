<?php

namespace Tests\Feature;

use App\Enums\ListingSource;
use App\Enums\SuggestionStatus;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\ExternalListing;
use App\Models\Line;
use App\Models\Product;
use App\Models\SwatchSuggestion;
use App\Models\User;
use App\Models\Variant;
use App\Services\Import\ImportReport;
use App\Services\Sources\ListingRulingService;
use App\Services\Swatches\SwatchSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class ListingReviewTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE = ListingSource::FiestaFactoryDirect;

    private const BASE = '/api/sources/fiesta_factory_direct';

    private Line $fiesta;

    private Color $turquoise;

    private Color $linen;

    private Product $bowl;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        $this->actingAs(User::factory()->create());

        $this->fiesta = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $this->turquoise = Color::create(['line_id' => $this->fiesta->id, 'name' => 'Turquoise', 'produced_from' => 1988, 'hex' => '#00a3a8']);
        $this->linen = Color::create(['line_id' => $this->fiesta->id, 'name' => 'Linen', 'produced_from' => 2025]);
        $this->bowl = Product::create(['line_id' => $this->fiesta->id, 'name' => 'Fruit Bowl']);

        foreach ([$this->turquoise, $this->linen] as $color) {
            Variant::create(['product_id' => $this->bowl->id, 'color_id' => $color->id, 'existence' => VariantExistence::Unconfirmed]);
        }

        $this->listing(1, 'Fruit/Salsa Bowl', 'Turquoise', 'https://cdn.example.test/turquoise.png');
        $this->listing(2, 'Fruit/Salsa Bowl', 'Linen', 'https://cdn.example.test/linen.png');
        $this->listing(3, 'Bistro Coupe Dinner Plate', 'Turquoise', null);
    }

    public function test_pending_names_come_with_counts_and_examples(): void
    {
        $colors = $this->getJson(self::BASE.'/names?kind=color')->assertOk()->json('data');

        $this->assertSame(['turquoise', 'linen'], array_column($colors, 'key'));
        $this->assertSame(2, $colors[0]['listings']);
        $this->assertSame('https://cdn.example.test/turquoise.png', $colors[0]['examples'][0]['image_url']);
    }

    public function test_mapping_both_names_confirms_the_variant_and_shows_its_evidence(): void
    {
        $this->rule('color', 'turquoise', $this->turquoise->id)->assertOk()->assertJsonPath('data.ruling.decision.value', 'mapped');

        $this->rule('product', 'fruit/salsa bowl', $this->bowl->id)
            ->assertOk()
            ->assertJsonPath('data.resolved', 1)
            ->assertJsonPath('data.confirmed_now', 1);

        $variant = $this->variant($this->turquoise);
        $this->assertSame(VariantExistence::Confirmed, $variant->existence);

        $this->getJson("/api/variants/{$variant->id}")
            ->assertOk()
            ->assertJsonPath('data.evidence.0.source.label', 'Fiesta Factory Direct')
            ->assertJsonPath('data.evidence.0.url', 'https://fiestafactorydirect.com/products/p-1');
    }

    public function test_mapping_a_store_name_gives_the_product_the_stores_wording(): void
    {
        $this->rule('product', 'fruit/salsa bowl', $this->bowl->id)
            ->assertOk()
            ->assertJsonPath('data.rename.from', 'Fruit Bowl')
            ->assertJsonPath('data.rename.to', 'Fruit/Salsa Bowl');

        $this->assertSame('Fruit/Salsa Bowl', $this->bowl->fresh()->name);
    }

    public function test_the_store_name_on_the_most_listings_wins(): void
    {
        $this->listing(7, 'Fruit Bowl Small', 'Linen', null);

        $this->rule('product', 'fruit bowl small', $this->bowl->id);
        $this->assertSame('Fruit Bowl Small', $this->bowl->fresh()->name);

        // "Fruit/Salsa Bowl" is on two listings, so it takes over once mapped too.
        $this->rule('product', 'fruit/salsa bowl', $this->bowl->id);
        $this->assertSame('Fruit/Salsa Bowl', $this->bowl->fresh()->name);
    }

    public function test_a_store_name_already_used_by_another_product_is_not_taken(): void
    {
        Product::create(['line_id' => $this->fiesta->id, 'name' => 'Fruit/Salsa Bowl']);

        $this->rule('product', 'fruit/salsa bowl', $this->bowl->id)
            ->assertOk()
            ->assertJsonPath('data.rename.to', null);

        $this->assertSame('Fruit Bowl', $this->bowl->fresh()->name);
        $this->assertStringContainsString('Merge', $this->rule('product', 'fruit/salsa bowl', $this->bowl->id)->json('data.rename.conflict'));
    }

    public function test_product_cards_list_their_colors_and_color_names_are_capitalized(): void
    {
        $this->listing(8, 'Fruit/Salsa Bowl', 'mulberry', null);
        app(ListingRulingService::class)->mapColor(self::SOURCE, 'turquoise', $this->turquoise);

        $product = collect($this->getJson(self::BASE.'/names?kind=product')->json('data'))->firstWhere('key', 'fruit/salsa bowl');

        $this->assertSame(['Linen', 'Mulberry', 'Turquoise'], array_column($product['colors'], 'name'));
        $this->assertSame('#00a3a8', collect($product['colors'])->firstWhere('name', 'Turquoise')['hex']);

        $colors = array_column($this->getJson(self::BASE.'/names?kind=color')->json('data'), 'name');
        $this->assertContains('Mulberry', $colors);
    }

    public function test_a_new_color_is_saved_capitalized(): void
    {
        $this->listing(9, 'Fruit/Salsa Bowl', 'cobalt blue', null);

        $this->postJson(self::BASE.'/rulings/create', ['kind' => 'color', 'key' => 'cobalt blue', 'name' => 'cobalt blue'])
            ->assertCreated();

        $this->assertTrue(Color::where('name', 'Cobalt Blue')->exists());
    }

    public function test_a_ruling_must_point_into_the_stores_line(): void
    {
        $harlequin = Line::create(['name' => 'Harlequin', 'slug' => 'harlequin']);
        $other = Color::create(['line_id' => $harlequin->id, 'name' => 'Turquoise']);

        $this->rule('color', 'turquoise', $other->id)->assertStatus(422);
    }

    public function test_undo_returns_the_name_to_pending_and_withdraws_evidence(): void
    {
        $this->rule('color', 'turquoise', $this->turquoise->id);
        $this->rule('product', 'fruit/salsa bowl', $this->bowl->id);

        $this->postJson(self::BASE.'/rulings/undo', ['kind' => 'product', 'key' => 'fruit/salsa bowl'])
            ->assertOk()
            ->assertJsonPath('data.ruling', null)
            ->assertJsonPath('data.resolved', 0);

        $this->assertSame(VariantExistence::Unconfirmed, $this->variant($this->turquoise)->existence);
        $this->assertContains('fruit/salsa bowl', array_column($this->getJson(self::BASE.'/names?kind=product')->json('data'), 'key'));
    }

    public function test_creating_a_color_uses_what_the_owner_typed(): void
    {
        $this->listing(4, 'Fruit/Salsa Bowl', 'Butterscotch', null);

        $this->postJson(self::BASE.'/rulings/create', [
            'kind' => 'color', 'key' => 'butterscotch', 'name' => 'Butterscotch', 'produced_from' => 2020, 'produced_to' => 2022,
        ])->assertCreated()->assertJsonPath('data.ruling.target.label', '2020-2022');

        $this->assertTrue(Color::where('name', 'Butterscotch')->where('produced_to', 2022)->exists());
    }

    public function test_several_product_names_can_become_products_at_once(): void
    {
        $this->postJson(self::BASE.'/rulings/create-products', ['keys' => ['fruit/salsa bowl', 'bistro coupe dinner plate']])
            ->assertCreated()
            ->assertJsonPath('data.created', 2);

        $this->assertTrue(Product::where('name', 'Bistro Coupe Dinner Plate')->exists());
        $this->assertSame([], $this->getJson(self::BASE.'/names?kind=product')->json('data'));
    }

    public function test_a_suggestion_is_the_median_across_photos_so_one_odd_shot_does_not_decide_it(): void
    {
        $this->listing(5, 'Salad Plate', 'Linen', 'https://cdn.example.test/linen-2.png');
        $this->listing(6, 'Mug', 'Linen', 'https://cdn.example.test/linen-dark.png');
        $this->fakePhotos([
            'linen.png' => [221, 212, 194],
            'linen-2.png' => [221, 212, 194],
            'linen-dark.png' => [120, 110, 100],
        ]);
        app(ListingRulingService::class)->mapColor(self::SOURCE, 'linen', $this->linen);

        app(SwatchSuggestionService::class)->suggest(self::SOURCE, new ImportReport);

        $this->assertSame('#ddd4c2', SwatchSuggestion::sole()->hex);
        $this->assertSame(3, SwatchSuggestion::sole()->photos_sampled);
    }

    public function test_a_suggestion_only_changes_the_color_once_accepted(): void
    {
        $this->fakePhotos(['turquoise.png' => [0, 163, 168], 'linen.png' => [221, 212, 194]]);
        app(ListingRulingService::class)->mapColor(self::SOURCE, 'turquoise', $this->turquoise);
        app(ListingRulingService::class)->mapColor(self::SOURCE, 'linen', $this->linen);

        app(SwatchSuggestionService::class)->suggest(self::SOURCE, new ImportReport);

        $suggestion = SwatchSuggestion::sole();
        $this->assertSame($this->linen->id, $suggestion->color_id);
        $this->assertSame('#ddd4c2', $suggestion->hex);
        $this->assertNull($this->linen->fresh()->hex);

        $this->postJson("/api/swatch-suggestions/{$suggestion->id}/accept")->assertOk();
        $this->assertSame('#ddd4c2', $this->linen->fresh()->hex);

        $this->assertSame(SuggestionStatus::Accepted, $suggestion->fresh()->status);
        $this->assertSame([], $this->getJson('/api/swatch-suggestions')->json('data'));
    }

    private function rule(string $kind, string $key, int $targetId)
    {
        return $this->postJson(self::BASE.'/rulings', [
            'kind' => $kind, 'key' => $key, 'decision' => 'mapped', 'target_id' => $targetId,
        ]);
    }

    private function variant(Color $color): Variant
    {
        return Variant::where('product_id', $this->bowl->id)->where('color_id', $color->id)->sole();
    }

    private function listing(int $id, string $product, string $color, ?string $image): void
    {
        ExternalListing::create([
            'source' => self::SOURCE,
            'external_product_id' => (string) $id,
            'external_variant_id' => (string) ($id * 100),
            'title' => "{$product} {$color}",
            'product_name' => $product,
            'product_key' => mb_strtolower($product),
            'color_name' => $color,
            'color_key' => mb_strtolower($color),
            'url' => "https://fiestafactorydirect.com/products/p-{$id}",
            'image_url' => $image,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Serve a plain square of each color on a white studio background.
     *
     * @param  array<string, array<int, int>>  $photos
     */
    private function fakePhotos(array $photos): void
    {
        $responses = [];

        foreach ($photos as $file => [$r, $g, $b]) {
            $image = imagecreatetruecolor(200, 200);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            imagefilledrectangle($image, 30, 30, 170, 170, imagecolorallocate($image, $r, $g, $b));

            ob_start();
            imagepng($image);
            $responses["cdn.example.test/{$file}*"] = Http::response(ob_get_clean());
        }

        Http::fake($responses);
    }
}
