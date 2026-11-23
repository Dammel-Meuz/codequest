<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword()
    {
        $validated = $this->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);
        $key = 'password-reset-attempt:'.Str::lower($validated['email']).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('email', 'Trop de tentatives. Réessaie dans '.RateLimiter::availableIn($key).' secondes.');
            return null;
        }

        RateLimiter::hit($key, 60);
        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            RateLimiter::clear($key);

            return redirect()->route('login')->with('status', 'password-reset');
        }

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', 'Un lien a déjà été demandé récemment. Vérifie ta boîte mail avant de réessayer.');
            return null;
        }

        if (! in_array($status, [Password::INVALID_TOKEN, Password::INVALID_USER], true)) {
            throw new \RuntimeException('Impossible de réinitialiser le mot de passe.');
        }

        $this->addError('email', 'Ce lien est invalide ou a expiré. Demande un nouveau lien de réinitialisation.');
        return null;
    }

    public function render()
    {
        return view('components.auth.⚡reset-password')
            ->layout('layouts.app', ['title' => 'Choisir un nouveau mot de passe']);
    }
};

?>

<div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-12">
    <section class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl"
             aria-labelledby="reset-password-title">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300">CodeQuest</p>
        <h1 id="reset-password-title" class="mt-3 text-2xl font-bold text-white">Choisir un nouveau mot de passe</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400">
            Choisis un mot de passe sécurisé d’au moins 8 caractères.
        </p>

        <form wire:submit="resetPassword" class="mt-6 space-y-4">
            <div>
                <label for="reset-email" class="mb-2 block text-sm font-medium text-slate-300">Adresse e-mail</label>
                <input id="reset-email" type="email" wire:model="email" autocomplete="email" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-indigo-500">
                @error('email')
                    <p class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="reset-password" class="mb-2 block text-sm font-medium text-slate-300">Nouveau mot de passe</label>
                <input id="reset-password" type="password" wire:model="password" autocomplete="new-password" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-indigo-500">
                @error('password')
                    <p class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="reset-password-confirmation" class="mb-2 block text-sm font-medium text-slate-300">
                    Confirmer le mot de passe
                </label>
                <input id="reset-password-confirmation" type="password" wire:model="password_confirmation"
                       autocomplete="new-password" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-indigo-500">
            </div>
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60">
                <span wire:loading.remove>Réinitialiser le mot de passe</span>
                <span wire:loading>Enregistrement en cours...</span>
            </button>
        </form>
    </section>
</div>
