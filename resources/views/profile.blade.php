
@php
    $user = auth()->user();

    $currentLevel = $user->currentLevel();
    $nextLevel = $user->nextLevel();

    $currentXp = $user->total_xp;

    if ($nextLevel && $currentLevel) {
        $xpNeeded = $nextLevel->min_xp - $currentLevel->min_xp;
        $xpProgress = $currentXp - $currentLevel->min_xp;

        $progress = $xpNeeded > 0
            ? min(100, max(0, ($xpProgress / $xpNeeded) * 100))
            : 0;
    } elseif ($currentLevel) {
        $progress = 100;
    } else {
        $progress = 0;
    }

    $completedExercises = $user->exerciseCompletions()->count();
@endphp
    @vite(['resources/css/app.css', 'resources/js/app.js'])

<div class="min-h-screen bg-gray-100 px-4 py-10">
    <div class="mx-auto max-w-4xl">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Mon profil
            </h1>
            <p class="mt-2 text-gray-600">
                Consulte tes informations et ta progression sur CodeQuest.
            </p>
        </div>

        <div class="rounded-2xl bg-white p-8 shadow-sm">

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-3xl font-bold text-indigo-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $user->name }}
                    </h2>
                    <p class="text-gray-500">
                        {{ $user->email }}
                    </p>
                    <span class="mt-2 inline-block rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-700">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>

            <hr class="my-8 border-gray-200">

            <h3 class="mb-5 text-xl font-bold text-gray-900">
                Ma progression
            </h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-indigo-50 p-5">
                    <p class="text-sm text-indigo-600">XP total</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-800">
                        {{ number_format($currentXp) }} XP
                    </p>
                </div>

                <div class="rounded-xl bg-purple-50 p-5">
                    <p class="text-sm text-purple-600">Niveau actuel</p>
                    <p class="mt-2 text-2xl font-bold text-purple-800">
                        {{ $currentLevel?->name ?? 'Débutant' }}
                    </p>
                    <p class="mt-1 text-sm text-purple-600">
                        {{ $currentLevel ? 'Niveau ' . $currentLevel->level_number : 'Niveau 1' }}
                    </p>
                </div>

                <div class="rounded-xl bg-green-50 p-5">
                    <p class="text-sm text-green-600">Exercices terminés</p>
                    <p class="mt-2 text-3xl font-bold text-green-800">
                        {{ $completedExercises }}
                    </p>
                </div>

                <div class="rounded-xl bg-orange-50 p-5">
                    <p class="text-sm text-orange-600">Prochain niveau</p>
                    <p class="mt-2 text-xl font-bold text-orange-800">
                        {{ $nextLevel?->name ?? 'Niveau maximal atteint' }}
                    </p>
                    @if($nextLevel)
                        <p class="mt-1 text-sm text-orange-600">
                            {{ number_format($nextLevel->min_xp - $currentXp) }} XP restants
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-8">
                <div class="mb-2 flex justify-between gap-4">
                    <h3 class="font-semibold text-gray-900">
                        Progression vers le prochain niveau
                    </h3>
                    <span class="font-semibold text-indigo-600">
                        {{ number_format($progress, 1) }} %
                    </span>
                </div>

                <div class="h-4 overflow-hidden rounded-full bg-gray-200">
                    <div
                        class="h-full rounded-full bg-indigo-600 transition-all"
                        style="width: {{ $progress }}%"
                    ></div>
                </div>

                @if($nextLevel)
                    <p class="mt-2 text-sm text-gray-500">
                        {{ number_format($currentXp) }} /
                        {{ number_format($nextLevel->min_xp) }} XP
                        pour atteindre le niveau {{ $nextLevel->level_number }}.
                    </p>
                @else
                    <p class="mt-2 text-sm text-green-600">
                        Félicitations ! Tu as atteint le niveau maximal.
                    </p>
                @endif
            </div>

            <hr class="my-8 border-gray-200">

            <div>
                <h3 class="mb-3 font-bold text-gray-900">
                    Informations du compte
                </h3>

                <p class="text-sm text-gray-600">
                    Membre depuis :
                    <span class="font-medium text-gray-900">
                        {{ $user->created_at->format('d/m/Y') }}
                    </span>
                </p>
            </div>

            <div class="mt-8">
                <a href="{{ route('dashboard') }}"
                   class="inline-block rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
                    Retour au tableau de bord
                </a>
            </div>

        </div>
    </div>
</div>
