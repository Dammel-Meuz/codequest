<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiration = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Réinitialise ton mot de passe CodeQuest')
            ->view('emails.auth.action', [
                'eyebrow' => 'Sécurité du compte',
                'title' => 'Réinitialise ton mot de passe',
                'greeting' => 'Bonjour '.$notifiable->name.',',
                'intro' => 'Nous avons reçu une demande de réinitialisation du mot de passe associé à ton compte CodeQuest.',
                'actionLabel' => 'Choisir un nouveau mot de passe',
                'actionUrl' => $url,
                'outro' => 'Si tu n’es pas à l’origine de cette demande, ignore cet e-mail. Ton mot de passe ne changera pas.',
                'expiration' => 'Ce lien expirera dans '.$expiration.' minutes.',
            ]);
    }
}
