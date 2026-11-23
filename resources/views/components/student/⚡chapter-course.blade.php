<?php

use App\Models\ChapterQuizQuestion;
use App\Models\LearningPathChapter;
use App\Models\User;
use App\Services\ChapterQuizService;
use Livewire\Component;

new class extends Component
{
    public LearningPathChapter $chapter;
    public ?string $feedback = null;
    public ?string $explanation = null;
    public ?bool $lastAnswerCorrect = null;
    public int $xpAwarded = 0;

    public function mount(LearningPathChapter $chapter): void
    {
        abort_unless(auth()->user()->role === 'student', 403);

        $this->chapter = $chapter->load('learningPath');

        abort_unless($this->chapter->learningPath->is_published, 404);
        abort_unless($this->chapter->isAccessibleFor(auth()->user()), 403, 'Termine les QCM des chapitres précédents pour continuer.');
    }

    public function answerQuestion(int $questionId, string $selectedOption): void
    {
        $this->resetErrorBag();
        $this->feedback = null;
        $this->explanation = null;
        $this->lastAnswerCorrect = null;
        $this->xpAwarded = 0;

        $validated = validator(
            ['selectedOption' => $selectedOption],
            ['selectedOption' => ['required', 'in:a,b,c,d']]
        )->validate();

        $user = auth()->user();
        abort_unless($this->chapter->isAccessibleFor($user), 403);

        $question = $this->chapter->quizQuestions()->findOrFail($questionId);

        if ($question->isCompletedBy($user)) {
            $this->feedback = 'Tu as déjà réussi ce QCM; tes XP ont été conservés.';
            $this->lastAnswerCorrect = true;
            return;
        }

        $nextQuestion = $this->chapter->quizQuestions()
            ->whereDoesntHave('completions', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        abort_unless($nextQuestion && $nextQuestion->id === $question->id, 403);

        $result = app(ChapterQuizService::class)->answer(
            $user,
            $question,
            $validated['selectedOption']
        );

        $this->lastAnswerCorrect = $result['correct'];

        if (! $result['correct']) {
            $this->feedback = 'Pas encore ! Relis la leçon et essaie une autre réponse.';
            return;
        }

        $this->xpAwarded = $result['xp_awarded'];
        $this->feedback = $result['already_completed']
            ? 'Tu as déjà réussi ce QCM; tes XP ont été conservés.'
            : 'Bonne réponse ! Tu avances dans le chapitre.';
        $this->explanation = $question->explanation;
    }

    public function render()
    {
        $userId = auth()->id();
        $questions = $this->chapter->quizQuestions()
            ->select([
                'id',
                'learning_path_chapter_id',
                'prompt',
                'options',
                'explanation',
                'xp_reward',
                'sort_order',
            ])
            ->withExists([
                'completions as is_completed' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->get();

        $completedQuestions = $questions->where('is_completed', true)->count();
        $questionCount = $questions->count();
        $chapterCompleted = $questionCount > 0 && $completedQuestions === $questionCount;

        $nextChapter = null;

        if ($chapterCompleted) {
            $nextChapter = LearningPathChapter::query()
                ->where('learning_path_id', $this->chapter->learning_path_id)
                ->whereHas('quizQuestions')
                ->where(function ($query) {
                    $query->where('sort_order', '>', $this->chapter->sort_order)
                        ->orWhere(function ($sameOrder) {
                            $sameOrder->where('sort_order', $this->chapter->sort_order)
                                ->where('id', '>', $this->chapter->id);
                        });
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        return view('components.student.⚡chapter-course', [
            'questions' => $questions,
            'currentQuestion' => $questions->first(fn ($question) => ! $question->is_completed),
            'completedQuestions' => $completedQuestions,
            'questionCount' => $questionCount,
            'chapterCompleted' => $chapterCompleted,
            'nextChapter' => $nextChapter,
            'xpEarned' => (int) $this->chapter->quizQuestions()
                ->join('chapter_quiz_completions', 'chapter_quiz_questions.id', '=', 'chapter_quiz_completions.chapter_quiz_question_id')
                ->where('chapter_quiz_completions.user_id', $userId)
                ->sum('chapter_quiz_completions.xp_earned'),
        ])->layout('layouts.app');
    }
};

?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('dashboard') }}" class="mb-6 inline-flex text-sm text-indigo-300 hover:underline">
            ← Retour au tableau de bord
        </a>

        <header class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
            <p class="text-sm font-medium text-cyan-300">{{ $chapter->learningPath->title }}</p>
            <h1 class="mt-2 text-3xl font-bold">{{ $chapter->title }}</h1>
            @if ($chapter->description)
                <p class="mt-3 leading-6 text-slate-400">{{ $chapter->description }}</p>
            @endif
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm">
                <span class="text-slate-300">QCM réussis : {{ $completedQuestions }} / {{ $questionCount }}</span>
                <span class="font-semibold text-yellow-300">{{ $xpEarned }} XP gagnés</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800"
                 role="progressbar"
                 aria-label="Progression des QCM du chapitre"
                 aria-valuemin="0"
                 aria-valuemax="{{ max(1, $questionCount) }}"
                 aria-valuenow="{{ $completedQuestions }}">
                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400 transition-all"
                     style="width: {{ $questionCount > 0 ? $completedQuestions / $questionCount * 100 : 0 }}%"></div>
            </div>
        </header>

        <section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
            <h2 class="text-xl font-semibold">La leçon</h2>
            @if ($chapter->course_content)
                <div class="mt-4 whitespace-pre-wrap break-words leading-7 text-slate-200">{{ $chapter->course_content }}</div>
            @else
                <p class="mt-4 text-slate-400">La leçon de ce chapitre sera bientôt disponible.</p>
            @endif
        </section>

        <section class="mt-6 rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-6 sm:p-8"
                 aria-labelledby="chapter-quiz-title">
            <div class="mb-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-300">Défi du chapitre</p>
                <h2 id="chapter-quiz-title" class="mt-1 text-2xl font-bold">Valide tes connaissances</h2>
                <p class="mt-2 text-sm text-slate-400">
                    Réussis chaque QCM pour débloquer le chapitre suivant. Les XP de chaque bonne réponse sont attribués une seule fois.
                </p>
            </div>

            @if ($feedback)
                <div @class([
                    'mb-5 rounded-xl border p-4',
                    'border-green-500/20 bg-green-500/10 text-green-200' => $lastAnswerCorrect,
                    'border-amber-500/20 bg-amber-500/10 text-amber-100' => $lastAnswerCorrect === false,
                ]) role="status" aria-live="polite">
                    <p class="font-semibold">{{ $feedback }}</p>
                    @if ($lastAnswerCorrect)
                        <p class="mt-1 text-sm">
                            {{ $xpAwarded > 0 ? '+'.$xpAwarded.' XP' : 'Aucun XP supplémentaire pour cette question.' }}
                        </p>
                    @endif
                    @if ($explanation)
                        <p class="mt-3 whitespace-pre-line text-sm leading-6">{{ $explanation }}</p>
                    @endif
                </div>
            @endif

            @if ($currentQuestion)
                <article wire:key="quiz-question-{{ $currentQuestion->id }}"
                         class="rounded-xl border border-slate-700 bg-slate-950/70 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Question {{ $completedQuestions + 1 }} sur {{ $questionCount }} · +{{ $currentQuestion->xp_reward }} XP
                    </p>
                    <h3 class="mt-3 text-lg font-semibold">{{ $currentQuestion->prompt }}</h3>
                    <div class="mt-5 grid gap-3">
                        @foreach ($currentQuestion->options as $optionKey => $option)
                            <button type="button"
                                    wire:click="answerQuestion({{ $currentQuestion->id }}, '{{ $optionKey }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="answerQuestion"
                                    class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-left transition hover:border-indigo-400 hover:bg-indigo-500/10 disabled:cursor-wait disabled:opacity-60">
                                <span class="mr-2 font-semibold text-indigo-300">{{ strtoupper($optionKey) }}.</span>
                                {{ $option }}
                            </button>
                        @endforeach
                    </div>
                    @error('selectedOption')
                        <p class="mt-3 text-sm text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </article>
            @elseif ($chapterCompleted)
                <div class="rounded-xl border border-green-500/20 bg-green-500/5 p-5">
                    <h3 class="text-lg font-semibold text-green-200">Chapitre terminé !</h3>
                    <p class="mt-2 text-sm text-slate-300">
                        Tu as réussi tous les QCM de ce chapitre et gagné {{ $xpEarned }} XP.
                    </p>
                    @if ($nextChapter)
                        <a href="{{ route('student.chapter-course', $nextChapter) }}"
                           class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold hover:bg-indigo-500">
                            Chapitre suivant : {{ $nextChapter->title }} →
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                           class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold hover:bg-indigo-500">
                            Retour au tableau de bord
                        </a>
                    @endif
                </div>
            @else
                <p class="rounded-xl border border-dashed border-slate-700 p-5 text-sm text-slate-400">
                    Les QCM de ce chapitre seront bientôt disponibles.
                </p>
            @endif
        </section>
    </div>
</div>
