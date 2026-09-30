<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $temporaryPassword,
        protected string $organizationName = ''
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your Inventory Management System Account')
            ->greeting("Hello {$notifiable->name},")
            ->line('An account has been created for you on the Inventory Management System.');

        if ($this->organizationName) {
            $mail->line("Organization: {$this->organizationName}");
        }

        return $mail
            ->line("Email: {$notifiable->email}")
            ->line("Temporary Password: {$this->temporaryPassword}")
            ->line('You will be required to change this password the first time you log in.')
            ->action('Log In', url(config('app.frontend_url', config('app.url'))))
            ->line('If you did not expect this account, please contact your administrator.');
    }
}
