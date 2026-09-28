<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_refuses_a_guest_with_the_standard_shape(): void
    {
        $this->getJson('/api/variants')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Not signed in', 'data' => null]);
    }

    public function test_the_app_shell_is_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_a_correct_password_signs_in_and_is_remembered(): void
    {
        $user = User::factory()->create(['password' => 'a-long-enough-password']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'a-long-enough-password'])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
        $this->getJson('/api/me')->assertOk();
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $user = User::factory()->create(['password' => 'a-long-enough-password']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'not-it'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/logout')->assertOk();

        $this->assertGuest('web');
    }

    public function test_make_user_creates_a_login_without_echoing_the_password(): void
    {
        $this->artisan('fiesta:make-user', ['email' => 'owner@example.com'])
            ->expectsQuestion('Password', 'a-long-enough-password')
            ->expectsQuestion('Password again', 'a-long-enough-password')
            ->expectsOutput('Created the owner login for owner@example.com.')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'owner@example.com')->exists());
    }

    public function test_make_user_refuses_a_short_password(): void
    {
        $this->artisan('fiesta:make-user', ['email' => 'owner@example.com'])
            ->expectsQuestion('Password', 'short')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}
