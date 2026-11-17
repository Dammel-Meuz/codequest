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
        request()->session()->regenerate();

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

    </div>
</div>
