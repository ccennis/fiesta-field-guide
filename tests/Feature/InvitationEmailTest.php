<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvitationEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Caroline']);
        Notification::fake();
    }

    public function test_an_invite_can_be_emailed_with_the_link_and_without_the_private_note(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/invitations', ['email' => 'jo@example.com', 'note' => 'Jo from the flea market'])
            ->assertCreated()
            ->assertJsonPath('data.sent_to', 'jo@example.com');

        $link = $response->json('data.link');

        Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($link) {
            $html = $notification->toMail($notifiable)->render();

            return $notifiable->routes['mail'] === 'jo@example.com'
                && $notification->link === $link
                && str_contains($html, $link)
                && str_contains($html, 'Caroline set a place for you')
                && str_contains($html, '/images/email/plates.png')
                && ! str_contains($html, 'flea market');
        });
    }

    public function test_without_an_email_nothing_is_sent(): void
    {
        $this->actingAs($this->admin)->postJson('/api/invitations', ['note' => 'Sam'])->assertCreated();

        Notification::assertNothingSent();
        $this->assertNull(Invitation::sole()->sent_to);
    }

    public function test_an_address_that_already_has_an_account_is_not_invited(): void
    {
        User::factory()->member()->create(['email' => 'jo@example.com']);

        $this->actingAs($this->admin)->postJson('/api/invitations', ['email' => 'jo@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'That email already has an account.');

        Notification::assertNothingSent();
        $this->assertSame(0, Invitation::count());
    }
}
