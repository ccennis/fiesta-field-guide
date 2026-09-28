<?php

namespace Tests\Feature;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use App\Enums\VariantExistence;
use App\Enums\WishlistPriority;
use App\Enums\WishlistSource;
use App\Models\Color;
use App\Models\ExternalListing;
use App\Models\Holding;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantEvidence;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $tester;

    private Color $heather;

    private Color $vintageCobalt;

    private Color $cobalt;

    private Product $carafe;

    private Product $mug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tester = User::factory()->member()->create();

        $line = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $this->heather = Color::create(['line_id' => $line->id, 'name' => 'Heather', 'produced_from' => 2006, 'produced_to' => 2009]);
        $this->vintageCobalt = Color::create(['line_id' => $line->id, 'name' => 'Cobalt', 'produced_from' => 1936, 'produced_to' => 1951]);
        $this->cobalt = Color::create(['line_id' => $line->id, 'name' => 'Cobalt', 'produced_from' => 1986]);
        $this->carafe = Product::create(['line_id' => $line->id, 'name' => 'Carafe']);
        $this->mug = Product::create(['line_id' => $line->id, 'name' => 'Tom And Jerry Mug']);

        foreach ([$this->heather, $this->vintageCobalt, $this->cobalt] as $color) {
            foreach ([$this->carafe, $this->mug] as $product) {
                Variant::create(['product_id' => $product->id, 'color_id' => $color->id, 'existence' => VariantExistence::Unconfirmed]);
            }
        }
    }

    public function test_a_color_search_lists_every_product_in_that_color_verified_or_not(): void
    {
        $this->variant($this->heather, $this->mug)->update(['existence' => VariantExistence::Confirmed]);

        $response = $this->actingAs($this->owner)->getJson('/api/browse?q=heath')->assertOk();

        $this->assertSame(['Heather'], collect($response->json('data.colors'))->pluck('name')->all());
        $this->assertSame([], $response->json('data.products'));

        $pieces = collect($response->json('data.colors.0.variants'))->keyBy('product.name');
        $this->assertCount(2, $pieces);
        $this->assertTrue($pieces['Tom And Jerry Mug']['existence']['confirmed']);
        $this->assertFalse($pieces['Carafe']['existence']['confirmed']);
    }

    public function test_a_product_search_groups_its_colors_by_era(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/browse?q=carafe')->assertOk();

        $eras = collect($response->json('data.products.0.eras'));
        $this->assertSame(['vintage', 'post_86'], $eras->pluck('era.value')->all());
        $this->assertSame(['Cobalt'], collect($eras[0]['variants'])->pluck('color.name')->all());
        $this->assertSame(['Cobalt', 'Heather'], collect($eras[1]['variants'])->pluck('color.name')->all());
    }

    public function test_a_store_name_the_owner_mapped_finds_the_product(): void
    {
        ProductAlias::create([
            'source' => ListingSource::FiestaFactoryDirect,
            'external_key' => 'tom & jerry footed mug',
            'decision' => AliasDecision::Mapped,
            'product_id' => $this->mug->id,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/browse?q=footed')->assertOk();

        $this->assertSame(['Tom And Jerry Mug'], collect($response->json('data.products'))->pluck('name')->all());
    }

    public function test_counts_and_wishlist_marks_are_the_viewers_own(): void
    {
        $heatherCarafe = $this->variant($this->heather, $this->carafe);
        Holding::create(['user_id' => $this->owner->id, 'variant_id' => $heatherCarafe->id]);
        Holding::create(['user_id' => $this->tester->id, 'variant_id' => $heatherCarafe->id]);
        Holding::create(['user_id' => $this->tester->id, 'variant_id' => $heatherCarafe->id]);
        WishlistItem::create([
            'user_id' => $this->tester->id,
            'product_id' => $this->mug->id,
            'priority' => WishlistPriority::Want,
            'source' => WishlistSource::Manual,
        ]);

        $tester = collect($this->actingAs($this->tester)->getJson('/api/browse?q=heather')->json('data.colors.0.variants'))->keyBy('product.name');
        $this->assertSame(2, $tester['Carafe']['owned_count']);
        $this->assertTrue($tester['Tom And Jerry Mug']['wishlisted']);

        $owner = collect($this->actingAs($this->owner)->getJson('/api/browse?q=heather')->json('data.colors.0.variants'))->keyBy('product.name');
        $this->assertSame(1, $owner['Carafe']['owned_count']);
        $this->assertFalse($owner['Tom And Jerry Mug']['wishlisted']);
    }

    public function test_a_search_needs_something_to_look_for(): void
    {
        $this->actingAs($this->owner)->getJson('/api/browse?q=h')->assertUnprocessable();
        $this->actingAs($this->owner)->getJson('/api/browse')->assertUnprocessable();
        $this->actingAs($this->owner)->getJson("/api/browse?color_id={$this->heather->id}")->assertOk()->assertJsonPath('data.colors.0.name', 'Heather');
    }

    public function test_the_owner_corrects_a_colors_years(): void
    {
        $this->actingAs($this->owner)
            ->patchJson("/api/colors/{$this->heather->id}", ['produced_from' => 2005, 'produced_to' => 2010])
            ->assertOk()
            ->assertJsonPath('data.produced_label', '2005-2010');

        $this->actingAs($this->owner)
            ->patchJson("/api/colors/{$this->heather->id}", ['produced_from' => 2010, 'produced_to' => 2005])
            ->assertUnprocessable();

        $this->actingAs($this->owner)
            ->patchJson("/api/colors/{$this->cobalt->id}", ['produced_from' => 1936, 'produced_to' => null])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'There is already a Cobalt starting in 1936.');
    }

    public function test_the_owner_marks_a_product_as_made_in_a_color_and_can_take_it_back(): void
    {
        $url = "/api/colors/{$this->heather->id}/made";

        $this->actingAs($this->owner)->postJson($url, ['product_id' => $this->carafe->id, 'made' => true])
            ->assertOk()
            ->assertJsonPath('data.made', true)
            ->assertJsonPath('data.by_owner', true);

        $variant = $this->variant($this->heather, $this->carafe);
        $this->assertSame(VariantExistence::Confirmed, $variant->existence);
        $this->assertNotNull($variant->confirmed_by_owner_at);

        $this->actingAs($this->owner)->postJson($url, ['product_id' => $this->carafe->id, 'made' => false])
            ->assertOk()
            ->assertJsonPath('data.made', false);

        $this->assertSame(VariantExistence::Unconfirmed, $variant->fresh()->existence);
    }

    public function test_a_piece_the_owner_has_or_a_store_shows_cannot_be_unchecked(): void
    {
        $owned = $this->variant($this->heather, $this->carafe);
        $owned->update(['existence' => VariantExistence::Confirmed]);
        Holding::create(['user_id' => $this->owner->id, 'variant_id' => $owned->id]);

        $listed = $this->variant($this->heather, $this->mug);
        $listed->update(['existence' => VariantExistence::Confirmed]);
        $listing = ExternalListing::create([
            'source' => ListingSource::FiestaFactoryDirect,
            'external_product_id' => '1',
            'external_variant_id' => '1',
            'title' => 'Tom & Jerry Mug - Heather',
            'product_name' => 'Tom & Jerry Mug',
            'product_key' => 'tom & jerry mug',
            'color_name' => 'Heather',
            'color_key' => 'heather',
            'url' => 'https://example.test/mug',
            'variant_id' => $listed->id,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        VariantEvidence::create(['variant_id' => $listed->id, 'external_listing_id' => $listing->id]);

        $checklist = collect($this->actingAs($this->owner)->getJson("/api/colors/{$this->heather->id}/checklist")->json('data'))->keyBy('product.name');
        $this->assertSame('You own one', $checklist['Carafe']['locked']);
        $this->assertSame('A store listing shows one', $checklist['Tom And Jerry Mug']['locked']);

        $this->actingAs($this->owner)
            ->postJson("/api/colors/{$this->heather->id}/made", ['product_id' => $this->carafe->id, 'made' => false])
            ->assertUnprocessable();

        $this->assertSame(VariantExistence::Confirmed, $owned->fresh()->existence);
    }

    public function test_a_tester_cannot_edit_colors(): void
    {
        $this->actingAs($this->tester)->patchJson("/api/colors/{$this->heather->id}", ['produced_from' => 1990])->assertForbidden();
        $this->actingAs($this->tester)->getJson("/api/colors/{$this->heather->id}/checklist")->assertForbidden();
        $this->actingAs($this->tester)->postJson("/api/colors/{$this->heather->id}/made", ['product_id' => $this->carafe->id, 'made' => true])->assertForbidden();
    }

    private function variant(Color $color, Product $product): Variant
    {
        return Variant::where('color_id', $color->id)->where('product_id', $product->id)->firstOrFail();
    }
}
