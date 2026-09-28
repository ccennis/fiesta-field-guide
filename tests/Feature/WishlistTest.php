<?php

namespace Tests\Feature;

use App\Enums\WishlistPriority;
use App\Enums\WishlistSource;
use App\Models\Color;
use App\Models\Decoration;
use App\Models\Line;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $plate;

    private Product $mug;

    private Variant $lilacPlate;

    private Variant $cobaltPlate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $line = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $lilac = Color::create(['line_id' => $line->id, 'name' => 'Lilac', 'produced_from' => 1993, 'produced_to' => 1995]);
        $cobalt = Color::create(['line_id' => $line->id, 'name' => 'Cobalt', 'produced_from' => 1986]);

        $this->plate = Product::create(['line_id' => $line->id, 'name' => '10" plate']);
        $this->mug = Product::create(['line_id' => $line->id, 'name' => 'T&J Mug']);

        $this->lilacPlate = Variant::create(['product_id' => $this->plate->id, 'color_id' => $lilac->id]);
        $this->cobaltPlate = Variant::create(['product_id' => $this->plate->id, 'color_id' => $cobalt->id]);
    }

    public function test_an_exact_item_beats_an_any_color_item(): void
    {
        $this->wish($this->plate->id, null);
        $exact = $this->wish($this->plate->id, $this->lilacPlate->id);

        $match = app(WishlistService::class)->matchFor($this->lilacPlate, $this->user);

        $this->assertTrue($match->is($exact));
    }

    public function test_an_any_color_item_matches_every_plain_color(): void
    {
        $anyColor = $this->wish($this->plate->id, null);

        $this->assertTrue(app(WishlistService::class)->matchFor($this->cobaltPlate, $this->user)->is($anyColor));
    }

    public function test_an_any_color_item_never_matches_a_decorated_piece(): void
    {
        $this->wish($this->mug->id, null);

        $decoration = Decoration::create(['name' => 'Cat Face']);
        $decorated = Variant::create([
            'product_id' => $this->mug->id,
            'color_id' => $this->cobaltPlate->color_id,
            'decoration_id' => $decoration->id,
        ]);

        $this->assertNull(app(WishlistService::class)->matchFor($decorated, $this->user));
    }

    public function test_a_duplicate_open_item_is_rejected(): void
    {
        $this->wish($this->plate->id, $this->lilacPlate->id);

        $this->postJson('/api/wishlist', [
            'product_id' => $this->plate->id,
            'variant_id' => $this->lilacPlate->id,
        ])->assertStatus(422);
    }

    public function test_a_variant_from_another_product_is_rejected(): void
    {
        $this->postJson('/api/wishlist', [
            'product_id' => $this->mug->id,
            'variant_id' => $this->lilacPlate->id,
        ])->assertStatus(422);

        $this->assertSame(0, WishlistItem::count());
    }

    public function test_recording_a_piece_fulfills_the_matching_item(): void
    {
        $item = $this->wish($this->plate->id, $this->lilacPlate->id);

        $response = $this->postJson('/api/holdings', ['variant_id' => $this->lilacPlate->id])
            ->assertCreated()
            ->assertJsonPath('data.fulfilled_wishlist_item.id', $item->id);

        $item->refresh();

        $this->assertNotNull($item->fulfilled_at);
        $this->assertSame($response->json('data.id'), $item->fulfilled_by_holding_id);
    }

    public function test_reopening_is_refused_when_the_target_is_already_open_again(): void
    {
        $fulfilled = $this->wish($this->plate->id, $this->lilacPlate->id, ['fulfilled_at' => now()]);
        $this->wish($this->plate->id, $this->lilacPlate->id);

        $this->patchJson("/api/wishlist/{$fulfilled->id}", ['fulfilled' => false])->assertStatus(422);
    }

    public function test_the_variant_list_filters_by_wishlist(): void
    {
        $this->wish($this->plate->id, $this->lilacPlate->id);

        $ids = collect($this->getJson('/api/variants?wishlisted=1')->json('data.items'))->pluck('id');
        $this->assertSame([$this->lilacPlate->id], $ids->all());

        $ids = collect($this->getJson('/api/variants?wishlisted=0')->json('data.items'))->pluck('id');
        $this->assertSame([$this->cobaltPlate->id], $ids->all());
    }

    public function test_grails_list_first(): void
    {
        $this->wish($this->plate->id, $this->lilacPlate->id);
        $grail = $this->wish($this->plate->id, $this->cobaltPlate->id, ['priority' => WishlistPriority::Grail]);

        $this->assertSame($grail->id, $this->getJson('/api/wishlist')->json('data.0.id'));
    }

    private function wish(int $productId, ?int $variantId, array $extra = []): WishlistItem
    {
        return WishlistItem::create($extra + [
            'user_id' => $this->user->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'priority' => WishlistPriority::Want,
            'source' => WishlistSource::Manual,
        ]);
    }
}
