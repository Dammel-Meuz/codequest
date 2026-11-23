
<?php

use App\Models\Exercise;
use App\Models\Submission;
use App\Models\StudentExerciseDraft;
use App\Services\GamificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public Exercise $exercise;
    public string $sourceCode = '';
    public string $draftSaveStatus = 'Pas encore enregistré';
    public int $revealedHintCount = 0;
    public ?Submission $submission = null;

    public function mount(Exercise $exercise)
    {
        if (!$exercise->is_published) {
            abort(404);
        }

        abort_unless(
            $exercise->isUnlockedFor(auth()->user()),
            403,
            'Cet exercice est verrouillé. Progresse jusqu’au niveau '.$exercise->requiredLevelNumber().' pour le débloquer.'
        );

        $this->exercise = $exercise;
        $this->refreshSubmission();
        $draft = StudentExerciseDraft::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exercise->id)
            ->first();

        $this->sourceCode = $draft?->source_code
            ?? ($exercise->starter_code ?: '// Écris ton code JavaScript ici');
        $this->draftSaveStatus = $draft ? 'Brouillon restauré' : 'Pas encore enregistré';
    }

    public function saveDraft(string $sourceCode): void
    {
        $validated = validator(
            ['sourceCode' => $sourceCode],
            ['sourceCode' => ['present', 'string', 'max:50000']]
        )->validate();

        $this->sourceCode = $validated['sourceCode'];
        $this->draftSaveStatus = 'Sauvegarde en cours…';

        StudentExerciseDraft::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'exercise_id' => $this->exercise->id,
            ],
            ['source_code' => $this->sourceCode]
        );

        $this->draftSaveStatus = 'Brouillon enregistré';
    }

    public function submitExercise()
    {
        $validated = $this->validate([
            'sourceCode' => ['required', 'string', 'max:50000'],
        ]);

        $this->submission = DB::transaction(function () use ($validated) {
            StudentExerciseDraft::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'exercise_id' => $this->exercise->id,
                ],
                ['source_code' => $validated['sourceCode']]
            );

            $submission = Submission::create([
                'user_id' => auth()->id(),
                'exercise_id' => $this->exercise->id,
                'source_code' => $validated['sourceCode'],
                'status' => 'pending',
            ]);

            app(GamificationService::class)->recordLearningActivity(
                auth()->user(),
                $submission->created_at
            );

            return $submission;
        });
        $this->draftSaveStatus = 'Brouillon enregistré';

        \App\Jobs\ProcessExerciseSubmission::dispatch($this->submission->id);
    }

    public function restoreSubmittedCode(): void
    {
        $submission = Submission::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $this->exercise->id)
            ->latest('id')
            ->firstOrFail();

        $this->sourceCode = $submission->source_code;
        $this->saveDraft($this->sourceCode);
        $this->dispatch('exercise-code-restored', code: $this->sourceCode);
    }

    public function revealNextHint(): void
    {
        $hintCount = count($this->exercise->hints ?? []);
        $this->revealedHintCount = min(max(0, $this->revealedHintCount), $hintCount);

        if ($this->revealedHintCount < $hintCount) {
            $this->revealedHintCount++;
        }
    }

    public function refreshSubmission()
    {
        $this->submission = Submission::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $this->exercise->id)
            ->orderByDesc('id')
            ->first();
    }

    public function render()
    {
        $nextExercise = null;

        if ($this->submission?->status === 'accepted') {
            $user = auth()->user();

            if ($this->exercise->learning_path_chapter_id) {
                $chapter = $this->exercise->chapter;
                $exercise = $this->exercise;

                $nextExercise = Exercise::query()
                    ->join('learning_path_chapters as next_chapters', 'next_chapters.id', '=', 'exercises.learning_path_chapter_id')
                    ->where('next_chapters.learning_path_id', $chapter->learning_path_id)
                    ->where('exercises.is_published', true)
                    ->where(function ($query) use ($chapter, $exercise) {
                        $query->where('next_chapters.sort_order', '>', $chapter->sort_order)
                            ->orWhere(function ($sameOrder) use ($chapter) {
                                $sameOrder->where('next_chapters.sort_order', $chapter->sort_order)
                                    ->where('next_chapters.id', '>', $chapter->id);
                            })
                            ->orWhere(function ($sameChapter) use ($chapter, $exercise) {
                                $sameChapter->where('next_chapters.id', $chapter->id)
                                    ->where(function ($exerciseOrder) use ($exercise) {
                                        $exerciseOrder->where('exercises.sort_order', '>', $exercise->sort_order)
                                            ->orWhere(function ($sameExerciseOrder) use ($exercise) {
                                                $sameExerciseOrder->where('exercises.sort_order', $exercise->sort_order)
                                                    ->where('exercises.id', '>', $exercise->id);
                                            });
                                    });
                            });
                    })
                    ->orderBy('next_chapters.sort_order')
                    ->orderBy('next_chapters.id')
                    ->orderBy('exercises.sort_order')
                    ->orderBy('exercises.id')
                    ->select('exercises.*')
                    ->first();
            } else {
                $nextExercise = Exercise::query()
                    ->where('category_id', $this->exercise->category_id)
                    ->where('is_published', true)
                    ->where(function ($query) {
                        $query->where('sort_order', '>', $this->exercise->sort_order)
                            ->orWhere(function ($sameOrder) {
                                $sameOrder->where('sort_order', $this->exercise->sort_order)
                                    ->where('id', '>', $this->exercise->id);
                            });
                    })
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();
            }

            if ($nextExercise && ! $nextExercise->isUnlockedFor($user)) {
                $nextExercise = null;
            }
        }

        return view('components.student.⚡exercise-show', [
            'nextExercise' => $nextExercise,
        ])
            ->layout('layouts.app');
    }
};
?>


<div
    class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8"
    @if ($submission && in_array($submission->status, ['pending', 'processing']))
        wire:poll.2s="refreshSubmission"
    @endif
>
    <div class="mx-auto max-w-5xl">

        <a href="{{ route('student.exercises') }}"
           class="mb-8 inline-flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300">
            ← Retour au catalogue
        </a>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8">

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <span class="rounded-full bg-indigo-500/10 px-3 py-1 text-sm text-indigo-400">
                    {{ $exercise->category->name }}
                </span>

                <span class="text-sm font-semibold text-yellow-400">
                    +{{ $exercise->xp_reward }} XP
                </span>
            </div>

            <h1 class="text-3xl font-bold">
                {{ $exercise->title }}
            </h1>

            <div class="mt-6">
                <h2 class="mb-3 text-lg font-semibold">Énoncé</h2>

                <div class="whitespace-pre-line leading-7 text-slate-300">
                    {{ $exercise->description }}
                </div>
            </div>

            <div class="mt-8">
                <h2 class="mb-3 text-lg font-semibold">Instructions</h2>

                <p class="leading-7 text-slate-300">
                    Lis attentivement l'énoncé et prépare ton code JavaScript
                    pour résoudre cet exercice.
                </p>
            </div>

            @if (! empty($exercise->hints))
                <section class="mt-8 rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-5"
                         aria-labelledby="exercise-hints-title">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="exercise-hints-title" class="font-semibold text-indigo-200">Besoin d’un coup de pouce ?</h2>
                            <p class="mt-1 text-sm text-slate-400">
                                Révèle les indices un par un pour garder le défi.
                            </p>
                        </div>

                        <span class="text-xs text-slate-400">
                            {{ min($revealedHintCount, count($exercise->hints)) }} / {{ count($exercise->hints) }} révélés
                        </span>
                    </div>

                    @if ($revealedHintCount > 0)
                        <ol class="mt-4 space-y-3">
                            @foreach (array_slice($exercise->hints, 0, $revealedHintCount) as $index => $hint)
                                <li wire:key="revealed-hint-{{ $index }}"
                                    class="rounded-lg border border-slate-700 bg-slate-950/60 p-3 text-sm text-slate-200">
                                    <span class="font-medium text-indigo-300">Indice {{ $index + 1 }}.</span>
                                    {{ $hint }}
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($revealedHintCount < count($exercise->hints))
                        <button type="button" wire:click="revealNextHint"
                                wire:loading.attr="disabled" wire:target="revealNextHint"
                                class="mt-4 rounded-lg border border-indigo-400/30 px-4 py-2 text-sm text-indigo-200 transition hover:bg-indigo-500/10 disabled:opacity-50">
                            Afficher l’indice {{ $revealedHintCount + 1 }}
                        </button>
                    @else
                        <p class="mt-4 text-xs text-slate-400">Tu as consulté tous les indices.</p>
                    @endif
                </section>
            @endif

            
            <div class="mt-8">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Éditeur JavaScript</h2>

                    <span class="rounded-full bg-yellow-500/10 px-3 py-1 text-xs text-yellow-400">
                        JavaScript
                    </span>
                </div>

                <div
                    id="monaco-editor"
                    data-code="{{ $sourceCode }}"
                    wire:ignore
                    role="region"
                    aria-label="Éditeur de code JavaScript"
                    class="min-h-100 overflow-hidden rounded-xl border border-slate-700"
                ></div>
                <p id="monaco-editor-status" role="status" aria-live="polite" class="mt-2 text-sm text-slate-400">
                    Préparation de l’éditeur…
                </p>
                <p id="exercise-draft-status" role="status" aria-live="polite" class="mt-1 text-xs text-slate-400">
                    {{ $draftSaveStatus }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Ton brouillon est enregistré automatiquement et reste distinct du code de ta dernière soumission.
                </p>

                <div class="mt-4">
                    <button
                        wire:click="submitExercise"
                        wire:loading.attr="disabled"
                        wire:target="submitExercise"
                        @disabled($submission && in_array($submission->status, ['pending', 'processing']))
                        class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitExercise">
                            @if ($submission && in_array($submission->status, ['pending', 'processing']))
                                Correction en cours…
                            @else
                                Soumettre ma réponse
                            @endif
                        </span>

                        <span wire:loading wire:target="submitExercise">
                            Envoi en cours...
                        </span>
                    </button>

                    @error('sourceCode')
                        <p id="source-code-error" class="mt-3 text-sm text-red-300" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                <p class="mt-3 text-sm text-slate-400">
                    Écris ta solution JavaScript dans cet éditeur. Tu peux aussi utiliser les raccourcis clavier habituels.
                </p>
            </div>


            @if ($submission)
                <div
                    class="mt-8 rounded-xl border border-slate-700 bg-slate-950/60 p-5"
                    aria-live="polite"
                    @if (in_array($submission->status, ['pending', 'processing'])) aria-busy="true" @endif
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Dernière soumission corrigée</h2>
                            <p class="mt-1 text-xs text-slate-400">
                                Envoyée le {{ $submission->created_at->format('d/m/Y à H:i') }}
                            </p>
                        </div>

                        @if ($submission->status === 'accepted')
                            <span class="rounded-full bg-green-500/10 px-3 py-1 text-sm font-medium text-green-400">
                                Réussi
                            </span>
                        @elseif ($submission->status === 'rejected')
                            <span class="rounded-full bg-red-500/10 px-3 py-1 text-sm font-medium text-red-400">
                                Échec
                            </span>
                        @elseif ($submission->status === 'error')
                            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-sm font-medium text-amber-400">
                                Erreur
                            </span>
                        @else
                            <span class="rounded-full bg-blue-500/10 px-3 py-1 text-sm font-medium text-blue-400">
                                {{ $submission->status === 'processing' ? 'Correction...' : 'En attente...' }}
                            </span>
                        @endif
                    </div>

                    <details class="mt-4 rounded-lg border border-slate-800 bg-slate-900/70">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-slate-200">
                            Voir le code envoyé pour cette soumission
                        </summary>
                        <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words border-t border-slate-800 p-4 font-mono text-xs text-slate-300">{{ $submission->source_code }}</pre>
                        <div class="border-t border-slate-800 p-3">
                            <button type="button" wire:click="restoreSubmittedCode"
                                    wire:loading.attr="disabled" wire:target="restoreSubmittedCode"
                                    class="rounded-lg border border-indigo-500/30 px-3 py-2 text-xs font-medium text-indigo-200 hover:bg-indigo-500/10 disabled:opacity-50">
                                Restaurer ce code dans le brouillon
                            </button>
                        </div>
                    </details>

                    @if (in_array($submission->status, ['pending', 'processing']))
                        <p class="mt-4 flex items-center gap-2 text-sm text-slate-300" role="status">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-indigo-300 border-t-transparent motion-reduce:animate-none" aria-hidden="true"></span>
                            Ton code est analysé, cette page se mettra à jour automatiquement.
                        </p>
                    @else
                        <p class="mt-4 text-sm text-slate-300">
                            Tests réussis :
                            <strong>{{ $submission->passed_tests }} / {{ $submission->total_tests }}</strong>
                        </p>

                        @if ($submission->status === 'accepted')
                            @if ($submission->xpTransaction)
                                <p class="mt-2 text-sm text-green-400">
                                    Félicitations ! Tu as gagné {{ $submission->xpTransaction->amount }} XP.
                                </p>
                            @else
                                <p class="mt-2 text-sm text-green-400">
                                    Exercice déjà terminé : cette réussite ne rapporte pas d’XP supplémentaire.
                                </p>
                            @endif
                            @if ($exercise->success_explanation)
                                <div class="mt-4 rounded-lg border border-green-500/20 bg-green-500/5 p-4">
                                    <h3 class="font-semibold text-green-200">À retenir</h3>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-200">
                                        {{ $exercise->success_explanation }}
                                    </p>
                                </div>
                            @endif
                            @if ($nextExercise)
                                <a href="{{ route('student.exercise-show', $nextExercise) }}"
                                   class="mt-5 inline-flex w-full items-center justify-between gap-4 rounded-xl bg-indigo-600 px-5 py-4 font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 sm:w-auto">
                                    <span>
                                        <span class="block text-xs font-medium text-indigo-200">Prêt pour la suite ?</span>
                                        <span class="mt-1 block">Exercice suivant : {{ $nextExercise->title }}</span>
                                    </span>
                                    <span class="text-xl" aria-hidden="true">→</span>
                                </a>
                            @endif
                        @elseif ($submission->status === 'rejected')
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                Compare les entrées, les résultats attendus et obtenus pour repérer l’écart.
                                Les détails des tests cachés restent confidentiels; essaie aussi les cas limites.
                            </p>
                        @elseif ($submission->status === 'error' && ! empty($submission->result['message']))
                            <p class="mt-3 text-sm text-amber-300">
                                {{ $submission->result['message'] }}
                            </p>
                        @endif

                        @if (in_array($submission->status, ['accepted', 'rejected']))
                            <div class="mt-4 space-y-3">
                                @foreach (($submission->result['tests'] ?? []) as $test)
                                    <div @class([
                                        'rounded-lg border p-3 text-sm',
                                        'border-green-500/20 bg-green-500/5' => $test['passed'],
                                        'border-red-500/20 bg-red-500/5' => ! $test['passed'],
                                    ])>
                                        @if (! empty($test['hidden']))
                                            <p @class([
                                                'font-medium text-green-300' => $test['passed'],
                                                'font-medium text-red-300' => ! $test['passed'],
                                            ])>
                                                Test caché {{ $test['passed'] ? 'réussi' : 'échoué' }}.
                                            </p>
                                        @else
                                            <p @class([
                                                'font-medium text-green-300' => $test['passed'],
                                                'font-medium text-red-300' => ! $test['passed'],
                                            ])>
                                                Test {{ $test['test_index'] }} — {{ $test['passed'] ? 'réussi' : 'échoué' }}
                                            </p>

                                            @if (! empty($test['error']))
                                                <p class="mt-2 break-words text-red-200">{{ $test['error'] }}</p>
                                            @else
                                                <p class="mt-2 break-words text-slate-300">
                                                    Entrée :
                                                    <code>{{ json_encode($test['input'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
                                                </p>
                                                <p class="mt-1 break-words text-slate-300">
                                                    Attendu :
                                                    <code>{{ json_encode($test['expected'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
                                                </p>
                                                <p class="mt-1 break-words text-slate-300">
                                                    Obtenu :
                                                    <code>{{ json_encode($test['actual'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
                                                </p>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>
