<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public Guest $guest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invitation_sent',
            'invitation_id' => $this->invitation->id,
            'guest_id' => $this->guest->id,
            'guest_name' => $this->guest->name,
            'message' => "Invitation successfully sent to {$this->guest->name}",
        ];
    }
}
