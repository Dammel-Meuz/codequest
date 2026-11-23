<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\Level;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;


new class extends Component
{
      public function render()
    {
        $gradedSubmissions = Submission::query()
            ->whereIn('status', ['accepted', 'rejected'])
            ->selectRaw("SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count")
            ->first();
        $acceptedCount = (int) ($gradedSubmissions->accepted_count ?? 0);
        $rejectedCount = (int) ($gradedSubmissions->rejected_count ?? 0);
        $gradedCount = $acceptedCount + $rejectedCount;

        $submissionStats = DB::table('submissions')
            ->select('exercise_id')
            ->selectRaw("SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count")
            ->selectRaw("SUM(CASE WHEN status IN ('accepted', 'rejected') THEN 1 ELSE 0 END) AS graded_count")
            ->selectRaw("SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS pending_count")
            ->selectRaw('COUNT(DISTINCT user_id) AS participating_students_count')
            ->groupBy('exercise_id');

        $completionStats = DB::table('exercise_completions')
            ->select('exercise_id')
            ->selectRaw('COUNT(DISTINCT user_id) AS completed_students_count')
            ->groupBy('exercise_id');

        $exerciseAnalytics = Exercise::query()
            ->with('category')
            ->leftJoinSub($submissionStats, 'submission_stats', 'submission_stats.exercise_id', '=', 'exercises.id')
            ->leftJoinSub($completionStats, 'completion_stats', 'completion_stats.exercise_id', '=', 'exercises.id')
            ->where('exercises.is_published', true)
            ->select('exercises.*')
            ->selectRaw('COALESCE(submission_stats.accepted_count, 0) AS accepted_submissions_count')
            ->selectRaw('COALESCE(submission_stats.rejected_count, 0) AS rejected_submissions_count')
            ->selectRaw('COALESCE(submission_stats.graded_count, 0) AS graded_submissions_count')
            ->selectRaw('COALESCE(submission_stats.pending_count, 0) AS pending_submissions_count')
            ->selectRaw('COALESCE(submission_stats.participating_students_count, 0) AS participating_students_count')
            ->selectRaw('COALESCE(completion_stats.completed_students_count, 0) AS completed_students_count')
            ->orderByDesc('graded_submissions_count')
            ->orderBy('exercises.title')
            ->get()
            ->map(function (Exercise $exercise) {
                $graded = (int) $exercise->graded_submissions_count;
                $accepted = (int) $exercise->accepted_submissions_count;
                $participants = (int) $exercise->participating_students_count;
                $completed = (int) $exercise->completed_students_count;
                $successRate = $graded > 0 ? round($accepted / $graded * 100, 1) : null;

                $exercise->setAttribute('success_rate', $successRate);
                $exercise->setAttribute('students_without_completion', max(0, $participants - $completed));
                $exercise->setAttribute(
                    'needs_review',
                    $graded >= 5 && $successRate !== null && $successRate < 50
                );

                return $exercise;
            });

        $totalPublishedExercises = Exercise::query()->where('is_published', true)->count();
        $studentProgress = User::query()
            ->where('role', 'student')
            ->withCount([
                'exerciseCompletions as completed_exercises_count' => fn ($query) => $query
                    ->whereHas('exercise', fn ($exercise) => $exercise->where('is_published', true)),
            ])
            ->withMax('submissions', 'created_at')
            ->selectSub(
                Level::query()
                    ->select('name')
                    ->whereColumn('levels.min_xp', '<=', 'users.total_xp')
                    ->orderByDesc('levels.min_xp')
                    ->limit(1),
                'current_level_name'
            )
            ->orderByRaw(
                '(SELECT MAX(created_at) FROM submissions WHERE submissions.user_id = users.id) DESC'
            )
            ->orderByDesc('users.created_at')
            ->limit(10)
            ->get();

        $recentActivity = Submission::query()
            ->with(['user:id,name', 'exercise:id,title'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view("components.admin.⚡dashboard", [
            'totalStudents' => User::where('role', 'student')->count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalExercises' => Exercise::count(),
            'totalCategories' => Category::count(),
            'totalSubmissions' => Submission::count(),
            'recentUsers' => User::latest()->take(5)->get(),
            'gradedSubmissionCount' => $gradedCount,
            'acceptedSubmissionCount' => $acceptedCount,
            'rejectedSubmissionCount' => $rejectedCount,
            'globalSuccessRate' => $gradedCount > 0
                ? round($acceptedCount / $gradedCount * 100, 1)
                : null,
            'pendingSubmissionCount' => Submission::query()
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'exerciseAnalytics' => $exerciseAnalytics,
            'studentProgress' => $studentProgress,
            'totalPublishedExercises' => $totalPublishedExercises,
            'recentActivity' => $recentActivity,
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 px-6 py-10 text-white">

    <div class="mx-auto max-w-7xl">

        <div class="mb-10">
            <h1 class="text-3xl font-bold text-blue-400">
                Administration CodeQuest
            </h1>

            <p class="mt-2 text-slate-400">
                Vue générale de la plateforme.
            </p>
        </div>

        <!-- Statistiques -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Étudiants</p>
                <p class="mt-3 text-3xl font-bold text-blue-400">
                    {{ $totalStudents }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Administrateurs</p>
                <p class="mt-3 text-3xl font-bold text-purple-400">
                    {{ $totalAdmins }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Exercices</p>
                <p class="mt-3 text-3xl font-bold text-green-400">
                    {{ $totalExercises }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Catégories</p>
                <p class="mt-3 text-3xl font-bold text-yellow-400">
                    {{ $totalCategories }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Soumissions</p>
                <p class="mt-3 text-3xl font-bold text-pink-400">
                    {{ $totalSubmissions }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Taux global de réussite</p>
                <p class="mt-3 text-3xl font-bold text-green-300">
                    {{ $globalSuccessRate !== null ? number_format($globalSuccessRate, 1).' %' : '—' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $acceptedSubmissionCount }} réussites sur {{ $gradedSubmissionCount }} corrections terminées
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Échecs corrigés</p>
                <p class="mt-3 text-3xl font-bold text-red-300">{{ $rejectedSubmissionCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Soumissions refusées par les tests</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Corrections en attente</p>
                <p class="mt-3 text-3xl font-bold text-amber-300">{{ $pendingSubmissionCount }}</p>
                <p class="mt-1 text-xs text-slate-500">En attente ou en cours de traitement</p>
            </div>

        </div>

        <section class="mt-10 rounded-xl border border-slate-800 bg-slate-900 p-6">
            <div class="mb-5">
                <h2 class="text-xl font-bold">Résultats par exercice</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Le taux est calculé sur les corrections terminées (réussites et échecs); les erreurs et attentes sont exclues.
                    « Sans réussite » désigne les étudiants ayant soumis l’exercice sans completion enregistrée.
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Signal à examiner : au moins 5 corrections terminées et moins de 50 % de réussite. Ce seuil est indicatif, pas un diagnostic.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-left text-sm">
                    <thead class="border-b border-slate-700 text-slate-400">
                        <tr>
                            <th class="p-3">Exercice</th>
                            <th class="p-3 text-right">Réussites</th>
                            <th class="p-3 text-right">Échecs</th>
                            <th class="p-3 text-right">Taux</th>
                            <th class="p-3 text-right">En attente</th>
                            <th class="p-3 text-right">Sans réussite</th>
                            <th class="p-3">Analyse</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($exerciseAnalytics as $exercise)
                            <tr>
                                <td class="p-3">
                                    <p class="font-medium">{{ $exercise->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $exercise->category->name }}</p>
                                </td>
                                <td class="p-3 text-right text-green-300">{{ $exercise->accepted_submissions_count }}</td>
                                <td class="p-3 text-right text-red-300">{{ $exercise->rejected_submissions_count }}</td>
                                <td class="p-3 text-right">
                                    {{ $exercise->success_rate !== null ? number_format($exercise->success_rate, 1).' %' : '—' }}
                                </td>
                                <td class="p-3 text-right text-slate-300">{{ $exercise->pending_submissions_count }}</td>
                                <td class="p-3 text-right text-slate-300">{{ $exercise->students_without_completion }}</td>
                                <td class="p-3">
                                    @if ($exercise->needs_review)
                                        <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-200">
                                            À examiner
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-5 text-center text-slate-400">
                                    Aucun exercice publié à analyser.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">Progression des étudiants</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Les 10 étudiants les plus récemment inscrits ou ayant soumis un exercice.
                    </p>
                </div>

                <div class="space-y-4">
                    @forelse ($studentProgress as $student)
                        @php
                            $studentCompletionPercentage = $totalPublishedExercises > 0
                                ? min(100, (int) round($student->completed_exercises_count / $totalPublishedExercises * 100))
                                : 0;
                        @endphp
                        <article class="border-t border-slate-800 pt-4 first:border-0 first:pt-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-medium">{{ $student->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $student->current_level_name ?? 'Niveau débutant' }} ·
                                        {{ number_format($student->total_xp) }} XP
                                    </p>
                                </div>
                                <p class="text-sm text-slate-300">
                                    {{ $student->completed_exercises_count }} / {{ $totalPublishedExercises }} réussis
                                </p>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800"
                                 role="progressbar"
                                 aria-label="Progression de {{ $student->name }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-valuenow="{{ $studentCompletionPercentage }}">
                                <div class="h-full rounded-full bg-indigo-500"
                                     style="width: {{ $studentCompletionPercentage }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                Dernière activité :
                                {{ $student->submissions_max_created_at
                                    ? \Illuminate\Support\Carbon::parse($student->submissions_max_created_at)->format('d/m/Y à H:i')
                                    : 'Aucune soumission' }}
                            </p>
                        </article>
                    @empty
                        <p class="text-sm text-slate-400">Aucun étudiant inscrit.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">Activité récente</h2>
                    <p class="mt-1 text-sm text-slate-400">Les 10 dernières soumissions, sans afficher le code source.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[460px] text-left text-sm">
                        <thead class="border-b border-slate-700 text-slate-400">
                            <tr>
                                <th class="py-2 pr-3">Étudiant</th>
                                <th class="py-2 pr-3">Exercice</th>
                                <th class="py-2 pr-3">Statut</th>
                                <th class="py-2 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse ($recentActivity as $submission)
                                <tr>
                                    <td class="py-3 pr-3 font-medium">{{ $submission->user->name }}</td>
                                    <td class="py-3 pr-3">{{ $submission->exercise->title }}</td>
                                    <td class="py-3 pr-3">
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
                                            @case('processing')
                                                <span class="text-blue-300">En cours</span>
                                                @break
                                            @default
                                                <span class="text-slate-300">En attente</span>
                                        @endswitch
                                    </td>
                                    <td class="whitespace-nowrap py-3 text-right text-xs text-slate-400">
                                        {{ $submission->created_at->format('d/m H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center text-slate-400">
                                        Aucune soumission enregistrée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Derniers utilisateurs -->
        <div class="mt-10 rounded-xl border border-slate-800 bg-slate-900 p-6">

            <h2 class="mb-6 text-xl font-bold">
                Derniers utilisateurs inscrits
            </h2>

            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="border-b border-slate-700 text-sm text-slate-400">
                        <tr>
                            <th class="p-3">Nom</th>
                            <th class="p-3">E-mail</th>
                            <th class="p-3">Rôle</th>
                            <th class="p-3">Inscription</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr class="border-b border-slate-800">
                                <td class="p-3">{{ $user->name }}</td>
                                <td class="p-3">{{ $user->email }}</td>
                                <td class="p-3">
                                    {{ ucfirst($user->role) }}
                                </td>
                                <td class="p-3">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-3 text-center text-slate-400">
                                    Aucun utilisateur enregistré.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>
        </div>

        <!-- Navigation administration -->
        <div class="mt-10">
            <h2 class="mb-5 text-xl font-bold">
                Gestion de la plateforme
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

               <a href="{{ route('admin.categories') }}"
                    class="block rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:border-blue-500 hover:bg-slate-800">

                        <h3 class="font-semibold text-blue-400">
                            Catégories
                        </h3>

                        <p class="mt-2 text-sm text-slate-400">
                            Gérer les catégories de programmation.
                        </p>

                        <p class="mt-4 text-sm font-semibold text-blue-400">
                            Accéder à la gestion →
                        </p>
                    </a>

                
                    <a href="{{ route('admin.exercises') }}"
                    class="block bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500 transition">
                        <h3 class="text-lg font-semibold text-white">Exercices</h3>
                        <p class="text-slate-400 text-sm mt-2">
                            Créer, modifier et gérer les exercices.
                        </p>
                        <span class="inline-block mt-4 text-indigo-400">
                            Accéder à la gestion →
                        </span>
                    </a>

                    <a href="{{ route('admin.learning-paths') }}"
                    class="block rounded-xl border border-slate-800 bg-slate-900 p-6 transition hover:border-cyan-500">
                        <h3 class="text-lg font-semibold text-white">Parcours</h3>
                        <p class="mt-2 text-sm text-slate-400">
                            Organiser les exercices en chapitres et définir les prérequis.
                        </p>
                        <span class="mt-4 inline-block text-cyan-300">
                            Accéder à la gestion →
                        </span>
                    </a>


                
                <a href="{{ route('admin.users') }}"
                class="block bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500 transition">
                    <h3 class="text-lg font-semibold text-white">Utilisateurs</h3>
                    <p class="text-slate-400 text-sm mt-2">
                        Consulter les comptes, les XP et gérer les rôles.
                    </p>
                    <span class="inline-block mt-4 text-indigo-400">
                        Accéder à la gestion →
                    </span>
                </a>
                <a href="{{ route('student.exercises') }}"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">
                    Explorer les exercices
                </a>


            </div>
        </div>

        <div class="mt-8">
            <a href="{{ route('dashboard') }}"
               class="text-blue-400 hover:underline">
                ← Retour au tableau de bord
            </a>
        </div>

    </div>

</div>