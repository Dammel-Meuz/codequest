
<?php

use App\Models\Exercise;
use Livewire\Component;

new class extends Component
{
    public Exercise $exercise;
    public string $sourceCode = '';
    public string $submissionMessage = '';

    public function mount(Exercise $exercise)
    {
        if (!$exercise->is_published) {
            abort(404);
        }

        $this->exercise = $exercise;
        $this->sourceCode = $exercise->starter_code ?: '// Écris ton code JavaScript ici';
    }
    public function submitExercise()
{
    $validated = $this->validate([
        'sourceCode' => ['required', 'string', 'max:50000'],
    ]);

    $submission = \App\Models\Submission::create([
        'user_id' => auth()->id(),
        'exercise_id' => $this->exercise->id,
        'source_code' => $validated['sourceCode'],
        'status' => 'pending',
    ]);

    \App\Jobs\ProcessExerciseSubmission::dispatch($submission->id);

    $this->submissionMessage = 'Ton code a été envoyé avec succès !';
}

    public function render()
    {
        return view('components.student.⚡exercise-show')
            ->layout('layouts.app');
    }
};
?>


<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
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

            
            <div class="mt-8">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Éditeur JavaScript</h2>

                    <span class="rounded-full bg-yellow-500/10 px-3 py-1 text-xs text-yellow-400">
                        JavaScript
                    </span>
                </div>

                <div
                    id="monaco-editor"
                    data-code="{{ $exercise->starter_code ?: '// Écris ton code JavaScript ici' }}"
                    wire:ignore
                    class="min-h-100 overflow-hidden rounded-xl border border-slate-700"
                ></div>

                <div class="mt-4">
                    <button
                        wire:click="submitExercise"
                        wire:loading.attr="disabled"
                        wire:target="submitExercise"
                        class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitExercise">
                            Soumettre ma réponse
                        </span>

                        <span wire:loading wire:target="submitExercise">
                            Envoi en cours...
                        </span>
                    </button>

                    @if ($submissionMessage)
                        <p class="mt-3 text-sm text-green-400">
                            {{ $submissionMessage }}
                        </p>
                    @endif

                    @error('sourceCode')
                        <p class="mt-3 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                <p class="mt-3 text-sm text-slate-400">
                    Écris ta solution JavaScript dans cet éditeur.
                </p>
            </div>


            <div class="mt-8 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                <p class="text-sm text-amber-300">
                    L'éditeur de code et le système de correction automatique
                    seront ajoutés dans les prochaines étapes.
                </p>
            </div>

        </div>
    </div>
</div>
