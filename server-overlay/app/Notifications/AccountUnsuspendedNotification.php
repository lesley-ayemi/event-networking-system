<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountUnsuspendedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your EventNetworking account has been reinstated')
            ->line('Your account is no longer suspended — you can log in as usual.')
            ->action('Log in', rtrim(config('frontend.url'), '/').'/login');
    }
}
