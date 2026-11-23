<?php

use App\Models\LearningPath;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public string $status = 'all';

    public function render()
    {
        $userId = auth()->id();
        $paths = LearningPath::query()
            ->where('is_published', true)
            ->whereHas('chapters.quizQuestions')
            ->with([
                'chapters' => fn ($query) => $query
                    ->whereHas('quizQuestions')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'quizQuestions' => fn ($questions) => $questions
                            ->select([
                                'id',
                                'learning_path_chapter_id',
                                'xp_reward',
                                'sort_order',
                            ])
                            ->with([
                                'completions' => fn ($completions) => $completions
                                    ->where('user_id', $userId)
                                    ->select([
                                        'id',
                                        'chapter_quiz_question_id',
                                        'xp_earned',
                                    ]),
                            ]),
                    ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($paths as $path) {
            $questionCount = 0;
            $completedQuestionCount = 0;
            $earnedXp = 0;
            $previousChaptersCompleted = true;

            foreach ($path->chapters as $chapter) {
                $chapterQuestionCount = $chapter->quizQuestions->count();
                $chapterCompletedQuestionCount = $chapter->quizQuestions
                    ->filter(fn ($question) => $question->completions->isNotEmpty())
                    ->count();

                $chapter->setAttribute('question_count', $chapterQuestionCount);
                $chapter->setAttribute('completed_question_count', $chapterCompletedQuestionCount);
                $chapter->setAttribute('earned_xp', $chapter->quizQuestions
                    ->flatMap(fn ($question) => $question->completions)
                    ->sum('xp_earned'));
                $chapter->setAttribute('is_completed', $chapterQuestionCount > 0
                    && $chapterCompletedQuestionCount === $chapterQuestionCount);
                $chapter->setAttribute('is_accessible', $previousChaptersCompleted);

                $questionCount += $chapterQuestionCount;
                $completedQuestionCount += $chapterCompletedQuestionCount;
                $earnedXp += $chapter->earned_xp;

                if (! $chapter->is_completed) {
                    $previousChaptersCompleted = false;
                }
            }

            $progress = $questionCount > 0
                ? (int) round($completedQuestionCount / $questionCount * 100)
                : 0;
            $path->setAttribute('question_count', $questionCount);
            $path->setAttribute('completed_question_count', $completedQuestionCount);
            $path->setAttribute('earned_xp', $earnedXp);
            $path->setAttribute('progress_percentage', $progress);
            $path->setAttribute('is_completed', $questionCount > 0 && $completedQuestionCount === $questionCount);
            $path->setAttribute('is_started', $completedQuestionCount > 0);
            $path->setRelation(
                'nextAccessibleChapter',
                $path->chapters->first(fn ($chapter) => $chapter->is_accessible && ! $chapter->is_completed)
            );
        }

        $filteredPaths = $paths
            ->filter(function ($path) {
                $searchableText = $path->title.' '.$path->description.' '.$path->chapters
                    ->pluck('title')
                    ->implode(' ');
                $matchesSearch = $this->search === ''
                    || Str::contains(
                        Str::lower($searchableText),
                        Str::lower(trim($this->search))
                    );

                $matchesStatus = match ($this->status) {
                    'not_started' => ! $path->is_started,
                    'in_progress' => $path->is_started && ! $path->is_completed,
                    'completed' => $path->is_completed,
                    default => true,
                };

                return $matchesSearch && $matchesStatus;
            })
            ->values();

        return view('components.student.⚡learning', [
            'paths' => $filteredPaths,
            'totalPaths' => $paths->count(),
            'completedPaths' => $paths->where('is_completed', true)->count(),
        ])->layout('layouts.app');
    }
};

?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <header class="mb-8 rounded-3xl border border-indigo-500/20 bg-gradient-to-br from-indigo-500/10 via-slate-900 to-cyan-500/5 p-6 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300">Ton espace de cours</p>
                    <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Apprentissage</h1>
                    <p class="mt-3 max-w-2xl leading-6 text-slate-300">
                        Avance chapitre par chapitre, valide tes connaissances avec les QCM et gagne des XP à chaque réussite.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-950/70 px-5 py-4">
                    <p class="text-sm text-slate-400">Parcours terminés</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ $completedPaths }} <span class="text-base font-medium text-slate-500">/ {{ $totalPaths }}</span>
                    </p>
                </div>
            </div>
        </header>

        <section class="mb-8 grid gap-4 md:grid-cols-[1fr_260px]" aria-label="Filtres des parcours">
            <div>
                <label for="learning-search" class="mb-2 block text-sm font-medium text-slate-300">Rechercher un cours</label>
                <input id="learning-search"
                       type="search"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Ex. JavaScript, variables..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-indigo-500">
            </div>
            <div>
                <label for="learning-status" class="mb-2 block text-sm font-medium text-slate-300">Progression</label>
                <select id="learning-status"
                        wire:model.live="status"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-indigo-500">
                    <option value="all">Tous les cours</option>
                    <option value="not_started">À commencer</option>
                    <option value="in_progress">En cours</option>
                    <option value="completed">Terminés</option>
                </select>
            </div>
        </section>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">Parcours disponibles</h2>
            <span class="text-sm text-slate-400">{{ $paths->count() }} parcours affiché(s)</span>
        </div>

        @forelse ($paths as $path)
            @php
                $nextChapter = $path->nextAccessibleChapter ?? $path->chapters->first();
                $statusLabel = $path->is_completed ? 'Terminé' : ($path->is_started ? 'En cours' : 'À commencer');
            @endphp
            <article class="mb-6 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 transition hover:border-indigo-500/50">
                <div class="p-5 sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div class="max-w-3xl">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-xl font-bold sm:text-2xl">{{ $path->title }}</h3>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-green-500/10 text-green-300' => $path->is_completed,
                                    'bg-indigo-500/10 text-indigo-300' => $path->is_started && ! $path->is_completed,
                                    'bg-slate-800 text-slate-300' => ! $path->is_started,
                                ])>
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            @if ($path->description)
                                <p class="mt-2 leading-6 text-slate-400">{{ $path->description }}</p>
                            @endif
                        </div>

                        @if ($nextChapter)
                            <a href="{{ route('student.chapter-course', $nextChapter) }}"
                               class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                {{ $path->is_completed ? 'Revoir le cours' : ($path->is_started ? 'Continuer' : 'Commencer le cours') }}
                                <span class="ml-2" aria-hidden="true">→</span>
                            </a>
                        @endif
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-[1fr_auto] sm:items-end">
                        <div>
                            <div class="mb-2 flex flex-wrap justify-between gap-2 text-sm">
                                <span class="text-slate-300">
                                    {{ $path->completed_question_count }} / {{ $path->question_count }} QCM réussis
                                </span>
                                <span class="font-semibold text-indigo-300">{{ $path->progress_percentage }}%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-800"
                                 role="progressbar"
                                 aria-label="Progression du cours {{ $path->title }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-valuenow="{{ $path->progress_percentage }}">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400 transition-all"
                                     style="width: {{ $path->progress_percentage }}%"></div>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-yellow-300">
                            {{ $path->earned_xp }} XP gagnés
                        </p>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($path->chapters as $index => $chapter)
                            <div @class([
                                'rounded-xl border p-4',
                                'border-slate-700 bg-slate-950/50' => $chapter->is_accessible,
                                'border-slate-800 bg-slate-950/30 opacity-75' => ! $chapter->is_accessible,
                            ])>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Chapitre {{ $index + 1 }}
                                        </p>
                                        <h4 class="mt-1 font-semibold text-slate-100">{{ $chapter->title }}</h4>
                                    </div>
                                    @if ($chapter->is_completed)
                                        <span class="text-sm font-semibold text-green-300" aria-label="Chapitre terminé">✓</span>
                                    @elseif (! $chapter->is_accessible)
                                        <span class="text-xs text-amber-300">Verrouillé</span>
                                    @endif
                                </div>
                                @if ($chapter->description)
                                    <p class="mt-2 text-xs leading-5 text-slate-400">{{ $chapter->description }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <span class="text-slate-400">
                                        {{ $chapter->completed_question_count }} / {{ $chapter->question_count }} QCM
                                    </span>
                                    <span class="text-yellow-300">+{{ $chapter->earned_xp }} XP</span>
                                </div>
                                @if ($chapter->is_accessible)
                                    <a href="{{ route('student.chapter-course', $chapter) }}"
                                       class="mt-4 inline-flex text-xs font-semibold text-cyan-300 hover:underline">
                                        {{ $chapter->is_completed ? 'Revoir le chapitre' : 'Ouvrir le chapitre' }}
                                    </a>
                                @else
                                    <p class="mt-4 text-xs text-slate-500">Réussis les QCM du chapitre précédent.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-10 text-center">
                <h3 class="text-lg font-semibold">Aucun cours trouvé</h3>
                <p class="mt-2 text-slate-400">
                    @if ($totalPaths === 0)
                        Les parcours publiés avec leurs QCM apparaîtront ici.
                    @else
                        Essaie de modifier ta recherche ou le filtre de progression.
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</div>
