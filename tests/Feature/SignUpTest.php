<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['name' => 'Caroline']);
        Notification::fake();
    }

    public function test_anyone_can_sign_up_and_is_sent_a_confirmation_email(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Pat', 'email' => 'pat@example.com', 'password' => 'a-good-password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role.value', 'member')
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.admin_name', null);

        $pat = User::where('email', 'pat@example.com')->sole();
        $this->assertAuthenticatedAs($pat);
        Notification::assertSentTo($pat, VerifyEmail::class);
    }

    public function test_an_unconfirmed_account_can_only_ask_who_it_is(): void
    {
        $pat = User::factory()->member()->unverified()->create();

        $this->actingAs($pat)->getJson('/api/me')->assertOk();
        $this->actingAs($pat)->getJson('/api/browse?q=heather')->assertForbidden();
        $this->actingAs($pat)->postJson('/api/email/resend')->assertOk();

        Notification::assertSentTo($pat, VerifyEmail::class);
    }

    public function test_the_emailed_link_confirms_the_address_without_being_signed_in(): void
    {
        $pat = User::factory()->member()->unverified()->create();
        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $pat->id,
            'hash' => sha1($pat->email),
        ]);

        $this->get($link)->assertRedirect('/?verified=1');
        $this->assertTrue($pat->fresh()->hasVerifiedEmail());
    }

    public function test_a_tampered_link_is_refused(): void
    {
        $pat = User::factory()->member()->unverified()->create();
        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $pat->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->get($link)->assertRedirect('/?verified=0');
        $this->get("/email/verify/{$pat->id}/".sha1($pat->email))->assertForbidden();
        $this->assertFalse($pat->fresh()->hasVerifiedEmail());
    }

    public function test_an_email_can_only_sign_up_once(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Pat', 'email' => User::first()->email, 'password' => 'a-good-password',
        ])->assertUnprocessable();
    }

    public function test_only_invited_friends_see_the_admins_collection(): void
    {
        $stranger = User::factory()->member()->create();
        $friend = User::factory()->invited()->create();

        $this->actingAs($stranger)->getJson('/api/variants?collection=admin')->assertUnprocessable();
        $this->actingAs($stranger)->getJson('/api/me')->assertJsonPath('data.admin_name', null);

        $this->actingAs($friend)->getJson('/api/variants?collection=admin')->assertOk();
        $this->actingAs($friend)->getJson('/api/me')->assertJsonPath('data.admin_name', 'Caroline');
    }

    public function test_the_admin_sees_everyone_who_joined(): void
    {
        $admin = User::first();
        User::factory()->member()->unverified()->create(['name' => 'Stranger']);
        User::factory()->invited()->create(['name' => 'Friend']);

        $members = collect($this->actingAs($admin)->getJson('/api/members')->assertOk()->json('data'))->keyBy('name');

        $this->assertFalse($members['Stranger']['invited']);
        $this->assertFalse($members['Stranger']['email_verified']);
        $this->assertTrue($members['Friend']['invited']);
    }
}
