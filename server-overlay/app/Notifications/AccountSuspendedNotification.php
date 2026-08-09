<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspendedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your EventNetworking account has been suspended')
            ->line('An administrator has suspended your account, and you have been signed out of all devices.')
            ->line("This is usually the result of one or more reports filed against your account. If you believe this is a mistake, reply to this email and we'll take a look.");
    }
}
