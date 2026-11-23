
<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'student',
        ]);

        event(new Registered($user));

        Auth::login($user);

        session()->regenerate();

        return redirect()->route('verification.notice');
    }
    public function render()
{
    return view('components.auth.⚡register')
        ->layout('layouts.app', ['title' => 'Inscription']);
}
};
?>

<div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 backdrop-blur-sm lg:grid-cols-[1.1fr_1fr]">
                <div class="flex flex-col justify-between bg-gradient-to-br from-indigo-600 via-violet-600 to-cyan-500 p-8 sm:p-10">
                    <div>
                        <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-50">
                            CodeQuest
                        </span>
                        <h1 class="mt-8 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Rejoins la prochaine génération de talents.
                        </h1>
                    </div>

                    <div class="mt-8 space-y-4 text-sm text-indigo-50/90">
                        <p>Crée ton compte pour suivre des défis, progresser et bâtir ton avenir numérique.</p>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-base">✓</span>
                            <span>Accès aux missions et quêtes</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-base">✓</span>
                            <span>Suivi de progression personnalisé</span>
                        </div>
                    </div>
                </div>

                <div class="p-8 sm:p-10">
                    <div class="mb-8">
                        <p class="text-sm font-medium uppercase tracking-[0.2em] text-indigo-300">Bienvenue</p>
                        <h2 class="mt-3 text-3xl font-bold text-white">Créer un compte</h2>
                    </div>

                    <form wire:submit="register" class="space-y-5">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Nom complet</label>
                            <input
                                id="name"
                                type="text"
                                wire:model="name"
                                placeholder="Votre nom complet"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            >
                            @error('name')
                                <span class="mt-2 block text-sm text-red-400">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Adresse e-mail</label>
                            <input
                                id="email"
                                type="email"
                                wire:model="email"
                                placeholder="exemple@email.com"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            >
                            @error('email')
                                <span class="mt-2 block text-sm text-red-400">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Mot de passe</label>
                            <input
                                id="password"
                                type="password"
                                wire:model="password"
                                placeholder="Minimum 8 caractères"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            >
                            @error('password')
                                <span class="mt-2 block text-sm text-red-400">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">
                                Confirmer le mot de passe
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                wire:model="password_confirmation"
                                placeholder="Répétez votre mot de passe"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            >
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center rounded-xl bg-indigo-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <span wire:loading.remove>S'inscrire</span>
                            <span wire:loading>Inscription en cours...</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-400">
                        Vous avez déjà un compte ?
                        <a href="{{ route('login') }}" class="font-medium text-indigo-300 transition hover:text-indigo-200">
                            Se connecter
                        </a>
                    </p>
                </div>
            </div>
    </div>
</div>
