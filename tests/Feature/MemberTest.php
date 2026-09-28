<?php

namespace Tests\Feature;

use App\Enums\VariantExistence;
use App\Enums\WishlistPriority;
use App\Enums\WishlistSource;
use App\Models\Color;
use App\Models\Holding;
use App\Models\Invitation;
use App\Models\Line;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $tester;

    private Product $plate;

    private Variant $lilacPlate;

    private Variant $cobaltPlate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Caroline']);
        $this->tester = User::factory()->invited()->create(['name' => 'Sam']);

        $line = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $lilac = Color::create(['line_id' => $line->id, 'name' => 'Lilac', 'produced_from' => 1993]);
        $cobalt = Color::create(['line_id' => $line->id, 'name' => 'Cobalt', 'produced_from' => 1986]);
        $this->plate = Product::create(['line_id' => $line->id, 'name' => 'Dinner Plate']);
        $this->lilacPlate = Variant::create(['product_id' => $this->plate->id, 'color_id' => $lilac->id, 'existence' => VariantExistence::Unconfirmed]);
        $this->cobaltPlate = Variant::create(['product_id' => $this->plate->id, 'color_id' => $cobalt->id, 'existence' => VariantExistence::Unconfirmed]);

        Holding::create(['user_id' => $this->owner->id, 'variant_id' => $this->lilacPlate->id]);
        Holding::create(['user_id' => $this->owner->id, 'variant_id' => $this->lilacPlate->id]);
    }

    public function test_a_tester_cannot_change_the_catalog(): void
    {
        $this->actingAs($this->tester);

        $this->postJson('/api/products', ['line_id' => $this->plate->line_id, 'name' => 'Teapot'])->assertForbidden();
        $this->patchJson("/api/products/{$this->plate->id}", ['name' => 'Renamed'])->assertForbidden();
        $this->getJson('/api/sources/fiesta_factory_direct/names?kind=color')->assertForbidden();
        $this->getJson('/api/swatch-suggestions')->assertForbidden();
        $this->postJson('/api/invitations')->assertForbidden();

        $this->assertSame('Dinner Plate', $this->plate->fresh()->name);
    }

    public function test_a_tester_keeps_their_own_collection_and_can_view_the_owners(): void
    {
        $this->actingAs($this->tester);

        $this->postJson('/api/holdings', ['variant_id' => $this->cobaltPlate->id])->assertCreated();

        $mine = collect($this->getJson('/api/variants?owned=1')->json('data.items'));
        $this->assertSame([$this->cobaltPlate->id], $mine->pluck('id')->all());

        $owners = collect($this->getJson('/api/variants?owned=1&collection=admin')->json('data.items'));
        $this->assertSame([$this->lilacPlate->id], $owners->pluck('id')->all());
        $this->assertSame(2, $owners->first()['owned_count']);

        $this->assertSame(1, $this->getJson('/api/collection/summary')->json('data.holdings'));
    }

    public function test_the_identify_answer_tells_a_tester_what_the_owner_has(): void
    {
        $this->actingAs($this->tester);

        $this->getJson("/api/variants/{$this->lilacPlate->id}")
            ->assertOk()
            ->assertJsonPath('data.owned_count', 0)
            ->assertJsonPath('data.admin.name', 'Caroline')
            ->assertJsonPath('data.admin.count', 2);
    }

    public function test_nobody_can_change_someone_elses_piece(): void
    {
        $this->actingAs($this->tester);
        $ownersPiece = Holding::where('user_id', $this->owner->id)->first();

        $this->patchJson("/api/holdings/{$ownersPiece->id}", ['condition' => 'damaged'])->assertNotFound();

        $this->assertNull($ownersPiece->fresh()->condition);
    }

    public function test_wishlists_are_private(): void
    {
        $item = WishlistItem::create([
            'user_id' => $this->owner->id,
            'product_id' => $this->plate->id,
            'variant_id' => $this->cobaltPlate->id,
            'priority' => WishlistPriority::Grail,
            'max_price' => 40,
            'source' => WishlistSource::Manual,
        ]);

        $this->actingAs($this->tester);

        $this->assertSame([], $this->getJson('/api/wishlist')->json('data'));
        $this->patchJson("/api/wishlist/{$item->id}", ['max_price' => 1])->assertNotFound();
        $this->deleteJson("/api/wishlist/{$item->id}")->assertNotFound();
        $this->assertNull($this->getJson("/api/variants/{$this->cobaltPlate->id}")->json('data.wishlist_item'));

        // A tester's new piece never crosses off the owner's wish.
        $this->postJson('/api/holdings', ['variant_id' => $this->cobaltPlate->id])->assertCreated();
        $this->assertNull($item->fresh()->fulfilled_at);
    }

    public function test_only_the_owners_pieces_confirm_a_variant(): void
    {
        $this->actingAs($this->tester)->postJson('/api/holdings', ['variant_id' => $this->cobaltPlate->id]);
        $this->assertSame(VariantExistence::Unconfirmed, $this->cobaltPlate->fresh()->existence);

        $this->actingAs($this->owner)->postJson('/api/holdings', ['variant_id' => $this->cobaltPlate->id]);
        $this->assertSame(VariantExistence::Confirmed, $this->cobaltPlate->fresh()->existence);
    }

    public function test_an_invite_link_lets_one_person_join_once(): void
    {
        $link = $this->actingAs($this->owner)
            ->postJson('/api/invitations', ['note' => 'For Jo'])
            ->assertCreated()
            ->json('data.link');

        $token = basename($link);
        $this->assertStringContainsString('/invite/', $link);
        $this->assertNull(Invitation::where('token_hash', $token)->first(), 'The token itself is never stored.');

        auth()->guard('web')->logout();

        $this->getJson("/api/invites/{$token}")->assertOk()->assertJsonPath('data.admin_name', $this->owner->name);
        $this->postJson("/api/invites/{$token}/accept", [
            'name' => 'Jo', 'email' => 'jo@example.com', 'password' => 'a-good-password',
        ])->assertCreated()->assertJsonPath('data.role.value', 'member');

        $this->assertAuthenticatedAs(User::where('email', 'jo@example.com')->sole());

        auth()->guard('web')->logout();

        $this->postJson("/api/invites/{$token}/accept", [
            'name' => 'Someone Else', 'email' => 'else@example.com', 'password' => 'a-good-password',
        ])->assertNotFound();
        $this->assertFalse(User::where('email', 'else@example.com')->exists());
    }

    public function test_an_expired_invite_link_does_nothing(): void
    {
        $link = $this->actingAs($this->owner)->postJson('/api/invitations')->json('data.link');
        auth()->guard('web')->logout();

        $this->travel(8)->days();

        $this->getJson('/api/invites/'.basename($link))->assertNotFound();
    }

    public function test_removing_access_blocks_sign_in_and_ends_the_session(): void
    {
        $this->tester->update(['password' => 'a-good-password']);
        $this->actingAs($this->tester)->getJson('/api/me')->assertOk();

        $this->actingAs($this->owner)->postJson("/api/members/{$this->tester->id}/disable")->assertOk();

        $this->actingAs($this->tester->fresh())->getJson('/api/me')->assertUnauthorized();

        auth()->guard('web')->logout();
        $this->postJson('/api/login', ['email' => $this->tester->email, 'password' => 'a-good-password'])->assertStatus(422);

        // Their pieces are kept.
        $this->assertTrue(User::find($this->tester->id)->exists);
    }

    public function test_the_owners_access_cannot_be_removed(): void
    {
        $this->actingAs($this->owner)->postJson("/api/members/{$this->owner->id}/disable")->assertStatus(422);
    }

    public function test_the_first_account_created_claims_pieces_imported_before_it(): void
    {
        User::query()->delete();
        $unclaimed = Holding::create(['variant_id' => $this->cobaltPlate->id]);

        $this->artisan('fiesta:make-user', ['email' => 'owner@example.com'])
            ->expectsQuestion('Password', 'a-long-enough-password')
            ->expectsQuestion('Password again', 'a-long-enough-password')
            ->assertSuccessful();

        $owner = User::admin();
        $this->assertSame('owner@example.com', $owner->email);
        $this->assertSame($owner->id, $unclaimed->fresh()->user_id);
    }
}
