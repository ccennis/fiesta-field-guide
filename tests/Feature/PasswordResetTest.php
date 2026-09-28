<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_asking_for_a_link_emails_one_that_opens_the_app(): void
    {
        $pat = User::factory()->member()->create(['email' => 'pat@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'pat@example.com'])->assertOk();

        Notification::assertSentTo($pat, ResetPassword::class, function (ResetPassword $notification) use ($pat) {
            $url = $notification->toMail($pat)->actionUrl;

            return str_contains($url, '/reset-password/'.$notification->token) && ! str_contains($url, 'pat%40example.com');
        });
    }

    public function test_an_unknown_email_gets_the_same_answer_and_no_email(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If that email has an account, a link to reset the password is on its way.');

        Notification::assertNothingSent();
    }

    public function test_a_member_without_access_is_not_sent_a_link(): void
    {
        User::factory()->member()->create(['email' => 'gone@example.com', 'disabled_at' => now()]);

        $this->postJson('/api/forgot-password', ['email' => 'gone@example.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_the_link_sets_a_new_password_and_confirms_the_email(): void
    {
        $pat = User::factory()->member()->unverified()->create(['email' => 'pat@example.com']);
        $token = Password::createToken($pat);

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'pat@example.com', 'password' => 'a-brand-new-one',
        ])->assertOk();

        $pat->refresh();
        $this->assertTrue(Hash::check('a-brand-new-one', $pat->password));
        $this->assertTrue($pat->hasVerifiedEmail());

        $this->postJson('/api/login', ['email' => 'pat@example.com', 'password' => 'a-brand-new-one'])->assertOk();
    }

    public function test_a_wrong_or_used_token_is_refused(): void
    {
        $pat = User::factory()->member()->create(['email' => 'pat@example.com']);
        $token = Password::createToken($pat);
        $body = ['token' => $token, 'email' => 'pat@example.com', 'password' => 'a-brand-new-one'];

        $this->postJson('/api/reset-password', ['token' => 'nope'] + $body)->assertUnprocessable();
        $this->postJson('/api/reset-password', $body)->assertOk();
        $this->postJson('/api/reset-password', $body)->assertUnprocessable();
    }
}
