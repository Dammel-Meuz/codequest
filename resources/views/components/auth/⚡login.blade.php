<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Clé de limitation basée sur l'e-mail et l'adresse IP.
        $key = Str::transliterate(
            Str::lower($validated['email']) . '|' . request()->ip()
        );

        // Vérifier si le nombre maximal de tentatives est atteint.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError(
                'email',
                "Trop de tentatives. Réessaie dans {$seconds} secondes."
            );

            return;
        }

        // Vérifier les identifiants.
        if (!Auth::attempt(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
            $this->remember
        )) {
            // Enregistrer une tentative échouée pendant 60 secondes.
            RateLimiter::hit($key, 60);

            $this->addError(
                'email',
                'Adresse e-mail ou mot de passe incorrect.'
            );

            return;
        }

        // Réinitialiser le compteur après une connexion réussie.
        RateLimiter::clear($key);

        // Régénérer la session pour la sécurité.
        session()->regenerate();

        return redirect()->route('dashboard');
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-slate-950 px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl">

        <h1 class="text-center text-3xl font-bold text-blue-400">
            CodeQuest
        </h1>

        <h2 class="mt-6 text-center text-xl font-semibold text-white">
            Connexion
        </h2>

        <p class="mt-2 text-center text-sm text-slate-400">
            Connecte-toi pour continuer ton apprentissage.
        </p>

        @if (session('error'))
            <p class="mt-5 rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-center text-sm text-red-200"
               role="alert">
                {{ session('error') }}
            </p>
        @endif

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('social.redirect', 'google') }}"
               class="flex items-center justify-center gap-3 rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 font-medium text-white transition hover:border-slate-500 hover:bg-slate-700">
                <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.6 17.74 9.5 24 9.5Z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.74 7.18l7.3 5.67c4.26-3.93 7.48-9.73 7.48-17.32Z"/>
                    <path fill="#FBBC05" d="M10.53 28.59a14.5 14.5 0 0 1 0-9.18l-7.98-6.19a24 24 0 0 0 0 21.56l7.98-6.19Z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.8l-7.3-5.67c-2.03 1.36-4.62 2.17-8.6 2.17-6.26 0-11.57-4.1-13.47-9.72l-7.98 6.19C6.51 43.62 14.62 48 24 48Z"/>
                </svg>
                Continuer avec Google
            </a>
            <a href="{{ route('social.redirect', 'github') }}"
               class="flex items-center justify-center gap-3 rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 font-medium text-white transition hover:border-slate-500 hover:bg-slate-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 .9a11.1 11.1 0 0 0-3.51 21.63c.56.1.76-.24.76-.54v-2.08c-3.1.67-3.76-1.32-3.76-1.32-.5-1.28-1.24-1.62-1.24-1.62-1.01-.7.08-.69.08-.69 1.12.08 1.71 1.15 1.71 1.15.99 1.7 2.6 1.21 3.23.93.1-.72.39-1.21.7-1.49-2.48-.28-5.09-1.24-5.09-5.52 0-1.22.44-2.22 1.15-3-.12-.28-.5-1.42.11-2.96 0 0 .94-.3 3.05 1.15a10.6 10.6 0 0 1 5.55 0c2.11-1.45 3.05-1.15 3.05-1.15.61 1.54.23 2.68.11 2.96.72.78 1.15 1.78 1.15 3 0 4.29-2.61 5.23-5.1 5.51.4.35.75 1.02.75 2.06v3.07c0 .3.2.65.77.54A11.1 11.1 0 0 0 12 .9Z"/>
                </svg>
                Continuer avec GitHub
            </a>
        </div>

        <div class="my-6 flex items-center gap-3 text-xs uppercase tracking-wider text-slate-500">
            <span class="h-px flex-1 bg-slate-700"></span>
            ou avec ton e-mail
            <span class="h-px flex-1 bg-slate-700"></span>
        </div>

        <form wire:submit="login" class="mt-8 space-y-5">

            <div>
                <label for="email" class="mb-2 block text-sm text-slate-300">
                    Adresse e-mail
                </label>

                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="username"
                    placeholder="exemple@email.com"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white outline-none focus:border-blue-500"
                >

                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm text-slate-300">
                    Mot de passe
                </label>

                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    placeholder="Ton mot de passe"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white outline-none focus:border-blue-500"
                >

                @error('password')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input
                    type="checkbox"
                    wire:model="remember"
                    class="rounded border-slate-600"
                >
                Se souvenir de moi
            </label>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
            >
                <span wire:loading.remove>Se connecter</span>
                <span wire:loading>Connexion en cours...</span>
            </button>

        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="text-blue-400 hover:underline">
                S'inscrire
            </a>
        </p>
        <p class="mt-3 text-center text-sm">
            <a href="{{ route('password.request') }}" class="text-indigo-300 hover:underline">
                Mot de passe oublié ?
            </a>
        </p>

        @if (session('status') === 'password-reset')
            <p class="mt-4 rounded-lg border border-green-500/20 bg-green-500/10 p-3 text-center text-sm text-green-200"
               role="status" aria-live="polite">
                Ton mot de passe a été réinitialisé. Tu peux te connecter.
            </p>
        @endif

    </div>
</div>
