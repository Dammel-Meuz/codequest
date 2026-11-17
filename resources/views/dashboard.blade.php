
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - CodeQuest</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">

    <header class="border-b border-slate-800 p-5">
        <div class="mx-auto flex max-w-6xl items-center justify-between">
            <h1 class="text-2xl font-bold text-blue-400">CodeQuest</h1>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="rounded-lg bg-red-600 px-4 py-2 hover:bg-red-700">
                    Déconnexion
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl p-8">
        <h2 class="text-3xl font-bold">Bienvenue sur CodeQuest !</h2>

        <p class="mt-3 text-slate-400">
            Tu es connecté à ton espace étudiant.
        </p>

        <div class="mt-8 rounded-xl border border-slate-800 bg-slate-900 p-6">
            <h3 class="text-xl font-semibold">Mon profil</h3>

            <p class="mt-4">
                Nom : {{ auth()->user()->name }}
            </p>

            <p class="mt-2">
                E-mail : {{ auth()->user()->email }}
            </p>

            <p class="mt-2">
                XP : {{ auth()->user()->total_xp }}
            </p>

            <p class="mt-2">
                Rôle : {{ auth()->user()->role }}
            </p>
            <div class="mt-6">
                <a href="{{ route('profile') }}"
                class="inline-block rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700">
                    Voir mon profil →
                </a>
            </div>
        </div>
    </main>

</body>
</html>
