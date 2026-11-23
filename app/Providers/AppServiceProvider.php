<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $expiration = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Vérifie ton adresse e-mail')
                ->view('emails.auth.action', [
                    'eyebrow' => 'Bienvenue sur CodeQuest',
                    'title' => 'Confirme ton adresse e-mail',
                    'greeting' => 'Bonjour '.$notifiable->name.',',
                    'intro' => 'Confirme ton adresse e-mail pour activer ton compte et commencer ton aventure CodeQuest.',
                    'actionLabel' => 'Vérifier mon adresse e-mail',
                    'actionUrl' => $url,
                    'outro' => 'Si tu n’as pas créé de compte CodeQuest, tu peux ignorer cet e-mail.',
                    'expiration' => 'Ce lien expirera dans '.$expiration.' minutes.',
                ]);
        });
    }
}
