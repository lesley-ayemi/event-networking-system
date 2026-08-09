<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventIntroductionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
        public readonly User $match,
        public readonly array $reasons,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Meet {$this->match->first_name} before {$this->event->name}")
            ->line("You're both attending \"{$this->event->name}\", and we think you'd get along.")
            ->line("Say hello to {$this->match->first_name} {$this->match->last_name}".
                ($this->match->job_title ? ", {$this->match->job_title}" : '').
                ($this->match->industry ? " ({$this->match->industry})" : '').'.');

        foreach ($this->reasons as $reason) {
            $mail->line("• {$reason}");
        }

        return $mail
            ->action('View event', rtrim(config('frontend.url'), '/')."/events/{$this->event->id}")
            ->line("You're receiving this because you're open to matching and have pre-event introductions turned on — you can turn them off from your profile settings.");
    }
}
