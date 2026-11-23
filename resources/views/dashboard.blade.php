
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - CodeQuest</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <a href="#main-content"
       class="sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-slate-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Aller au contenu principal
    </a>
    @include('components.navigation')

    <main id="main-content" tabindex="-1" class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold">Bienvenue, {{ $user->name }} !</h2>
                <p class="mt-2 text-slate-400">Voici ta progression, tes activités et les défis à découvrir.</p>
            </div>
            <div class="rounded-xl border border-orange-500/20 bg-orange-500/5 px-4 py-3 text-sm">
                <span class="text-slate-400">Série actuelle</span>
                <strong class="ml-2 text-orange-300">{{ $activeStreak }} jour(s)</strong>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">XP total</p>
                <p class="mt-2 text-3xl font-bold text-yellow-300">{{ number_format($currentXp) }}</p>
                <p class="mt-1 text-sm text-slate-500">points d’expérience</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Niveau actuel</p>
                <p class="mt-2 text-2xl font-bold text-purple-300">{{ $currentLevel?->name ?? 'Débutant' }}</p>
                <p class="mt-1 text-sm text-slate-500">
                    Niveau {{ $currentLevel?->level_number ?? 1 }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Exercices réussis</p>
                <p class="mt-2 text-3xl font-bold text-green-300">{{ $completedCount }}</p>
                <p class="mt-1 text-sm text-slate-500">défis terminés</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400">Exercices en cours</p>
                <p class="mt-2 text-3xl font-bold text-blue-300">{{ $startedExercises->count() }}</p>
                <p class="mt-1 text-sm text-slate-500">à reprendre ou terminer</p>
            </div>
        </section>

        <section class="rounded-2xl border border-cyan-500/30 bg-gradient-to-br from-cyan-500/10 via-slate-900 to-indigo-500/10 p-6"
                 aria-labelledby="weekly-quest-title">
            @php
                $weeklyQuest = $weeklyQuestProgress->weeklyQuest;
                $weeklyQuestPercent = min(100, (int) round(
                    ($weeklyQuestProgress->progress / $weeklyQuest->goal_count) * 100
                ));
            @endphp
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-cyan-300">Quête de la semaine</p>
                    <h3 id="weekly-quest-title" class="mt-1 text-xl font-bold">{{ $weeklyQuest->title }}</h3>
                    <p class="mt-2 text-sm text-slate-300">{{ $weeklyQuest->description }}</p>
                    <p class="mt-2 text-xs text-slate-400">
                        Du {{ $weeklyQuest->week_start->format('d/m') }}
                        au {{ $weeklyQuest->week_start->copy()->addDays(6)->format('d/m') }}
                    </p>
                </div>
                <div class="rounded-xl border border-yellow-400/20 bg-yellow-400/10 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-yellow-300">+{{ $weeklyQuest->reward_xp }}</p>
                    <p class="text-xs text-yellow-100/70">XP bonus</p>
                </div>
            </div>
            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">
                        {{ $weeklyQuestProgress->completed_at ? 'Quête terminée !' : 'Exercices réussis' }}
                    </span>
                    <span class="font-semibold text-cyan-200">
                        {{ $weeklyQuestProgress->progress }} / {{ $weeklyQuest->goal_count }}
                    </span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-800"
                     role="progressbar"
                     aria-label="Progression de la quête hebdomadaire"
                     aria-valuemin="0"
                     aria-valuemax="{{ $weeklyQuest->goal_count }}"
                     aria-valuenow="{{ $weeklyQuestProgress->progress }}">
                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-indigo-400 transition-all"
                         style="width: {{ $weeklyQuestPercent }}%"></div>
                </div>
            </div>
            @if ($weeklyQuestProgress->completed_at)
                <p class="mt-3 text-sm font-medium text-green-300">
                    Bravo ! Les {{ $weeklyQuest->reward_xp }} XP bonus ont été ajoutés à ton total.
                </p>
            @else
                <a href="{{ route('student.exercises') }}"
                   class="mt-4 inline-flex rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-500">
                    Continuer les exercices
                </a>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">Progression vers le prochain niveau</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        @if ($nextLevel)
                            {{ number_format($currentXp) }} / {{ number_format($nextLevel->min_xp) }} XP
                            pour atteindre {{ $nextLevel->name }}.
                        @else
                            Tu as atteint le niveau maximal.
                        @endif
                    </p>
                </div>
                <span class="font-semibold text-indigo-300">{{ number_format($levelProgress, 1) }}%</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-slate-800"
                 role="progressbar"
                 aria-label="Progression vers le prochain niveau"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="{{ (int) round($levelProgress) }}">
                <div class="h-full rounded-full bg-indigo-500 transition-all"
                     style="width: {{ $levelProgress }}%"></div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-xl font-semibold">À reprendre</h3>
                        <p class="mt-1 text-sm text-slate-400">Tes exercices commencés mais pas encore réussis.</p>
                    </div>
                    <a href="{{ route('student.exercises') }}" class="text-sm text-indigo-300 hover:underline">
                        Tous les exercices
                    </a>
                </div>

                @forelse ($startedExercises as $exercise)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 py-4 first:border-0">
                        <div>
                            <p class="font-medium">{{ $exercise->title }}</p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $exercise->category->name }} ·
                                @switch($exercise->latest_submission_status)
                                    @case('pending')
                                    @case('processing')
                                        Correction en cours
                                        @break
                                    @case('rejected')
                                        À corriger
                                        @break
                                    @case('error')
                                        À réessayer
                                        @break
                                    @default
                                        En cours
                                @endswitch
                            </p>
                        </div>
                        <a href="{{ route('student.exercise-show', $exercise) }}"
                           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500">
                            Reprendre
                        </a>
                    </div>
                @empty
                    <p class="border-t border-slate-800 py-5 text-sm text-slate-400">
                        Aucun exercice en cours. Choisis un défi pour commencer !
                    </p>
                @endforelse
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-xl font-semibold">Recommandations</h3>
                        <p class="mt-1 text-sm text-slate-400">Des défis disponibles adaptés à ton niveau.</p>
                    </div>
                    <a href="{{ route('leaderboard') }}" class="text-sm text-indigo-300 hover:underline">
                        Classement
                    </a>
                </div>

                @forelse ($recommendations as $exercise)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 py-4 first:border-0">
                        <div>
                            <p class="font-medium">{{ $exercise->title }}</p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $exercise->category->name }} · {{ ucfirst($exercise->difficulty) }} ·
                                +{{ $exercise->xp_reward }} XP
                            </p>
                        </div>
                        <a href="{{ route('student.exercise-show', $exercise) }}"
                           class="rounded-lg border border-indigo-500/40 px-4 py-2 text-sm font-medium text-indigo-300 hover:bg-indigo-500/10">
                            Découvrir
                        </a>
                    </div>
                @empty
                    <p class="border-t border-slate-800 py-5 text-sm text-slate-400">
                        Tu as terminé tous les exercices actuellement débloqués. Continue à gagner de l’XP pour ouvrir le niveau suivant.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h3 class="text-xl font-semibold">Historique des soumissions</h3>
                    <a href="{{ route('profile') }}" class="text-sm text-indigo-300 hover:underline">
                        Voir mon profil
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-slate-400">
                            <tr>
                                <th class="py-2 pr-4">Date</th>
                                <th class="py-2 pr-4">Exercice</th>
                                <th class="py-2 pr-4">Résultat</th>
                                <th class="py-2 text-right">XP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse ($recentSubmissions as $submission)
                                <tr>
                                    <td class="whitespace-nowrap py-3 pr-4 text-slate-400">
                                        {{ $submission->created_at->format('d/m H:i') }}
                                    </td>
                                    <td class="py-3 pr-4 font-medium">
                                        {{ $submission->exercise->title }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        @switch($submission->status)
                                            @case('accepted')
                                                <span class="text-green-300">Réussi</span>
                                                @break
                                            @case('rejected')
                                                <span class="text-red-300">À corriger</span>
                                                @break
                                            @case('error')
                                                <span class="text-amber-300">Erreur</span>
                                                @break
                                            @default
                                                <span class="text-blue-300">En cours</span>
                                        @endswitch
                                    </td>
                                    <td class="whitespace-nowrap py-3 text-right text-yellow-300">
                                        {{ $submission->xpTransaction?->amount ?? 0 }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center text-slate-400">
                                        Tes soumissions apparaîtront ici.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-6">
                    <p class="text-sm text-indigo-200">Classement général</p>
                    @if ($leaderboardPosition)
                        <p class="mt-2 text-3xl font-bold">#{{ $leaderboardPosition }}</p>
                        <p class="mt-1 text-sm text-slate-400">sur le classement par XP total</p>
                    @else
                        <p class="mt-2 text-sm text-slate-400">Le classement est réservé aux étudiants.</p>
                    @endif
                    <a href="{{ route('leaderboard') }}"
                       class="mt-4 inline-block text-sm font-medium text-indigo-300 hover:underline">
                        Voir le classement complet →
                    </a>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Badges obtenus</h3>
                        <a href="{{ route('profile') }}" class="text-xs text-indigo-300 hover:underline">
                            Tous les badges
                        </a>
                    </div>
                    @forelse ($badges as $badge)
                        <div class="border-t border-slate-800 py-3 first:border-0">
                            <p class="font-medium text-indigo-200">{{ $badge->name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $badge->description }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">
                            Réussis des exercices et maintiens ta série pour gagner tes premiers badges.
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</body>
</html>
