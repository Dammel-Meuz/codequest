@component('layouts.app', ['title' => 'Vérifie ton adresse e-mail'])
    <div class="flex min-h-[calc(100vh-5rem)] items-center justify-center bg-slate-950 px-4 py-12">
        <section class="w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl sm:p-8"
                 aria-labelledby="verification-title">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">CodeQuest</p>
            <h1 id="verification-title" class="mt-3 text-2xl font-bold text-white">
                Vérifie ton adresse e-mail
            </h1>
            <p class="mt-4 leading-6 text-slate-300">
                Merci pour ton inscription ! Avant de commencer, clique sur le lien de vérification que nous venons de t’envoyer.
                Si tu ne l’as pas reçu, tu peux demander un nouvel envoi.
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-5 rounded-lg border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-200"
                   role="status" aria-live="polite">
                    Un nouveau lien de vérification vient d’être envoyé à ton adresse e-mail.
                </p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        Renvoyer le lien
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg border border-slate-700 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:bg-slate-800">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </section>
    </div>
@endcomponent
