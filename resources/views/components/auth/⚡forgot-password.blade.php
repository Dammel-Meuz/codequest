<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public ?string $statusMessage = null;

    public function sendResetLink(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $key = 'password-reset:'.Str::lower($validated['email']).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Trop de demandes. Réessaie dans '.RateLimiter::availableIn($key).' secondes.');
            return;
        }

        RateLimiter::hit($key, 60);
        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', 'Un lien a déjà été demandé récemment. Attends un instant avant de réessayer.');
            return;
        }

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            throw new \RuntimeException('Impossible d’envoyer le lien de réinitialisation du mot de passe.');
        }

        $this->statusMessage = 'Si un compte correspond à cette adresse, un lien de réinitialisation vient de lui être envoyé.';
    }

    public function render()
    {
        return view('components.auth.⚡forgot-password')
            ->layout('layouts.app', ['title' => 'Mot de passe oublié']);
    }
};

?>

<div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-12">
    <section class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl"
             aria-labelledby="forgot-password-title">
        <a href="{{ route('login') }}" class="text-sm text-indigo-300 hover:underline">← Retour à la connexion</a>
        <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300">CodeQuest</p>
        <h1 id="forgot-password-title" class="mt-3 text-2xl font-bold text-white">Mot de passe oublié ?</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400">
            Indique l’adresse e-mail de ton compte. Nous t’enverrons un lien sécurisé pour choisir un nouveau mot de passe.
        </p>

        @if ($statusMessage)
            <p class="mt-5 rounded-lg border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-200"
               role="status" aria-live="polite">
                {{ $statusMessage }}
            </p>
        @endif

        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label for="forgot-email" class="mb-2 block text-sm font-medium text-slate-300">Adresse e-mail</label>
                <input id="forgot-email" type="email" wire:model="email" autocomplete="email" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-indigo-500">
                @error('email')
                    <p class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60">
                <span wire:loading.remove>Envoyer le lien</span>
                <span wire:loading>Envoi en cours...</span>
            </button>
        </form>
    </section>
</div>
