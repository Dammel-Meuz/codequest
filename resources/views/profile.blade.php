
@php
    $user = auth()->user();

    $currentLevel = $user->currentLevel();
    $nextLevel = $user->nextLevel();

    $currentXp = $user->total_xp;
    $streak = $user->learningStreak;
    $activeStreak = $streak
        && $streak->last_activity_date
        && $streak->last_activity_date->gte(today()->subDay());
    $currentStreak = $activeStreak ? $streak->current_streak : 0;
    $badges = \App\Models\Badge::orderBy('criteria_value')->get();
    $earnedBadges = $user->badges()->get()->keyBy('id');
    $recentSubmissions = $user->submissions()
        ->with(['exercise', 'xpTransaction'])
        ->orderByDesc('id')
        ->take(10)
        ->get();

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

<div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
    @include('components.navigation')
    <main id="main-content" tabindex="-1" class="mx-auto max-w-4xl py-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white">
                Mon profil
            </h1>
            <p class="mt-2 text-slate-400">
                Consulte tes informations et ta progression sur CodeQuest.
            </p>
        </div>

        <div class="rounded-2xl bg-white p-5 text-gray-900 shadow-sm sm:p-8">

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-3xl font-bold text-indigo-700" aria-hidden="true">
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

                <div class="h-4 overflow-hidden rounded-full bg-gray-200"
                     role="progressbar"
                     aria-label="Progression vers le prochain niveau"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-valuenow="{{ (int) round($progress) }}">
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

            <section>
                <h3 class="mb-5 text-xl font-bold text-gray-900">
                    Série d’apprentissage
                </h3>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-orange-50 p-5">
                        <p class="text-sm text-orange-600">Série actuelle</p>
                        <p class="mt-2 text-3xl font-bold text-orange-800">
                            {{ number_format($currentStreak) }} jour(s)
                        </p>
                        <p class="mt-1 text-sm text-orange-600">
                            Soumets une solution chaque jour pour continuer ta série.
                        </p>
                    </div>

                    <div class="rounded-xl bg-amber-50 p-5">
                        <p class="text-sm text-amber-600">Meilleure série</p>
                        <p class="mt-2 text-3xl font-bold text-amber-800">
                            {{ number_format($streak?->longest_streak ?? 0) }} jour(s)
                        </p>
                    </div>
                </div>
            </section>

            <hr class="my-8 border-gray-200">

            <section>
                <h3 class="mb-5 text-xl font-bold text-gray-900">
                    Badges et récompenses
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($badges as $badge)
                        @php
                            $earnedBadge = $earnedBadges->get($badge->id);
                        @endphp

                        <div @class([
                            'rounded-xl border p-4',
                            'border-indigo-200 bg-indigo-50' => $earnedBadge,
                            'border-gray-200 bg-gray-50 opacity-70' => ! $earnedBadge,
                        ])>
                            <div class="flex items-start gap-3">
                                <span class="text-2xl" aria-hidden="true">
                                    {{ match ($badge->icon) {
                                        'compass' => '🧭',
                                        'trophy' => '🏆',
                                        'spark' => '✨',
                                        'crown' => '👑',
                                        'flame' => '🔥',
                                        'calendar' => '📅',
                                        default => '🏅',
                                    } }}
                                </span>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $badge->name }}</h4>
                                    <p class="mt-1 text-sm text-gray-600">{{ $badge->description }}</p>
                                    @if ($earnedBadge)
                                        <p class="mt-2 text-xs font-medium text-indigo-700">
                                            Obtenu le {{ \Carbon\Carbon::parse($earnedBadge->pivot->awarded_at)->format('d/m/Y') }}
                                        </p>
                                    @else
                                        <p class="mt-2 text-xs font-medium text-gray-500">À débloquer</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <hr class="my-8 border-gray-200">

            <section>
                <h3 class="mb-5 text-xl font-bold text-gray-900">
                    Historique des activités
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-gray-200 text-sm text-gray-500">
                            <tr>
                                <th class="p-3">Date</th>
                                <th class="p-3">Exercice</th>
                                <th class="p-3">Résultat</th>
                                <th class="p-3">Tests</th>
                                <th class="p-3">XP gagnés</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentSubmissions as $submission)
                                <tr>
                                    <td class="whitespace-nowrap p-3 text-sm text-gray-500">
                                        {{ $submission->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="p-3 font-medium text-gray-900">
                                        @if ($submission->exercise->is_published)
                                            <a href="{{ route('student.exercise-show', $submission->exercise) }}"
                                               class="text-indigo-600 hover:underline">
                                                {{ $submission->exercise->title }}
                                            </a>
                                        @else
                                            {{ $submission->exercise->title }}
                                        @endif
                                    </td>
                                    <td class="p-3 text-sm">
                                        @switch($submission->status)
                                            @case('accepted')
                                                <span class="font-medium text-green-700">Réussi</span>
                                                @break
                                            @case('rejected')
                                                <span class="font-medium text-red-700">À corriger</span>
                                                @break
                                            @case('error')
                                                <span class="font-medium text-amber-700">Erreur</span>
                                                @break
                                            @default
                                                <span class="font-medium text-blue-700">En cours</span>
                                        @endswitch
                                    </td>
                                    <td class="whitespace-nowrap p-3 text-sm text-gray-600">
                                        {{ $submission->passed_tests }} / {{ $submission->total_tests }}
                                    </td>
                                    <td class="whitespace-nowrap p-3 text-sm font-medium text-indigo-700">
                                        {{ $submission->xpTransaction?->amount ?? 0 }} XP
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-sm text-gray-500">
                                        Aucune activité pour le moment. Lance ton premier exercice !
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

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

            <hr class="my-8 border-gray-200">

            <section aria-labelledby="change-password-title">
                <h3 id="change-password-title" class="mb-2 text-xl font-bold text-gray-900">
                    Changer le mot de passe
                </h3>
                <p class="mb-5 text-sm text-gray-600">
                    Confirme ton mot de passe actuel avant d’en choisir un nouveau.
                </p>

                @if (session('status') === 'password-updated')
                    <p class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
                       role="status" aria-live="polite">
                        Ton mot de passe a été changé avec succès.
                    </p>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="max-w-xl space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-medium text-gray-700">
                            Mot de passe actuel
                        </label>
                        <input id="current_password" name="current_password" type="password"
                               autocomplete="current-password" required
                               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="new_password" class="mb-2 block text-sm font-medium text-gray-700">
                            Nouveau mot de passe
                        </label>
                        <input id="new_password" name="password" type="password"
                               autocomplete="new-password" required
                               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">
                            Confirmer le nouveau mot de passe
                        </label>
                        <input id="new_password_confirmation" name="password_confirmation" type="password"
                               autocomplete="new-password" required
                               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">
                        Enregistrer le nouveau mot de passe
                    </button>
                </form>
            </section>

            <div class="mt-8">
                <a href="{{ route('leaderboard') }}"
                   class="inline-block rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
                    Voir le classement
                </a>
                <a href="{{ route('dashboard') }}"
                   class="ml-3 inline-block rounded-lg bg-gray-200 px-5 py-3 font-semibold text-gray-800 hover:bg-gray-300">
                    Retour au tableau de bord
                </a>
            </div>

        </div>
    </div>
</div>
