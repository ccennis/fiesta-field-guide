<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invite link, emailed to a friend the admin named. The admin's private
 * note about who it is for is never included.
 */
class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $link,
        public string $adminName,
        public int $daysValid,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            'link' => $this->link,
            'adminName' => $this->adminName,
            'daysValid' => $this->daysValid,
            'platesUrl' => url('/images/email/plates.png'),
            'logoUrl' => url('/icons/logo.png'),
        ];

        return (new MailMessage)
            ->subject("{$this->adminName} saved you a seat at the Fiesta table")
            ->view(['emails.invitation', 'emails.invitation-text'], $data);
    }
}
