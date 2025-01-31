<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationMail extends Notification
{
    public function __construct(
        private readonly string $verificationCode
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vérification de votre email universitaire')
            ->line('Votre code de vérification est : ' . $this->verificationCode)
            ->line('Ce code est valable pendant 1 heure.')
            ->action('Retourner à l\'application', config('app.url'))
            ->line('Merci d\'utiliser notre application !');
    }
}
