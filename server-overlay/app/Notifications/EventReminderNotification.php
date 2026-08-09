<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Event $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->event->starts_at->diffForHumans();
        $where = $this->event->is_virtual ? 'This is a virtual event.' : ($this->event->location ?? 'Location TBA');

        return (new MailMessage)
            ->subject("Reminder: {$this->event->name} is coming up")
            ->line("\"{$this->event->name}\" starts {$when}.")
            ->line($where)
            ->action('View event', rtrim(config('frontend.url'), '/')."/events/{$this->event->id}")
            ->line("You're receiving this because you registered and have event reminders turned on — you can turn them off from your profile settings.");
    }
}
