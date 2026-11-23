<?php

use App\Models\ChapterQuizQuestion;
use App\Models\LearningPathChapter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public LearningPathChapter $chapter;
    public string $courseContent = '';
    public array $questions = [];

    public function mount(LearningPathChapter $chapter): void
    {
        $this->chapter = $chapter->load('learningPath');
        $this->courseContent = $chapter->course_content ?? '';
        $this->questions = $chapter->quizQuestions()
            ->withCount('completions')
            ->get()
            ->map(fn (ChapterQuizQuestion $question) => [
                'id' => $question->id,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct_option' => $question->correct_option,
                'explanation' => $question->explanation ?? '',
                'xp_reward' => $question->xp_reward,
                'sort_order' => $question->sort_order,
                'completion_count' => $question->completions_count,
            ])
            ->all();
    }

    public function addQuestion(): void
    {
        if (count($this->questions) >= 25) {
            $this->addError('questions', 'Un chapitre peut contenir au maximum 25 QCM.');
            return;
        }

        $this->questions[] = [
            'prompt' => '',
            'options' => ['a' => '', 'b' => '', 'c' => '', 'd' => ''],
            'correct_option' => 'a',
            'explanation' => '',
            'xp_reward' => 5,
            'sort_order' => count($this->questions),
            'completion_count' => 0,
        ];
        $this->resetValidation('questions');
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);

        foreach ($this->questions as $order => $question) {
            $this->questions[$order]['sort_order'] = $order;
        }

        $this->resetValidation('questions');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'courseContent' => ['required', 'string', 'max:30000'],
            'questions' => ['required', 'array', 'min:1', 'max:25'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.prompt' => ['required', 'string', 'max:1000'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.a' => ['required', 'string', 'max:500'],
            'questions.*.options.b' => ['required', 'string', 'max:500'],
            'questions.*.options.c' => ['required', 'string', 'max:500'],
            'questions.*.options.d' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'in:a,b,c,d'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.xp_reward' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $existingIds = $this->chapter->quizQuestions()->pluck('id')->map(fn ($id) => (int) $id);
        $submittedIds = collect($validated['questions'])
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($submittedIds->diff($existingIds)->isNotEmpty()) {
            $this->addError('questions', 'Un QCM envoyé ne correspond pas à ce chapitre.');
            return;
        }

        $removedIds = $existingIds->diff($submittedIds);

        if ($removedIds->isNotEmpty() && ChapterQuizQuestion::query()
            ->whereIn('id', $removedIds)
            ->whereHas('completions')
            ->exists()) {
            $this->addError('questions', 'Un QCM déjà réussi par un étudiant ne peut pas être supprimé.');
            return;
        }

        DB::transaction(function () use ($validated) {
            $this->chapter->update(['course_content' => $validated['courseContent']]);
            $savedQuestionIds = [];

            foreach ($validated['questions'] as $order => $questionData) {
                $question = isset($questionData['id'])
                    ? $this->chapter->quizQuestions()->whereKey($questionData['id'])->firstOrFail()
                    : new ChapterQuizQuestion;

                $question->fill([
                    'prompt' => $questionData['prompt'],
                    'options' => $questionData['options'],
                    'correct_option' => $questionData['correct_option'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'xp_reward' => $questionData['xp_reward'],
                    'sort_order' => $order,
                ]);
                $this->chapter->quizQuestions()->save($question);
                $savedQuestionIds[] = $question->id;
            }

            $this->chapter->quizQuestions()->whereNotIn('id', $savedQuestionIds)->delete();
        });

        $this->mount($this->chapter->fresh());
        session()->flash('success', 'Le cours et ses QCM ont été enregistrés.');
    }

    public function render()
    {
        return view('components.admin.⚡chapter-course')
            ->layout('layouts.app');
    }
};

?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('admin.learning-paths') }}" class="mb-6 inline-flex text-sm text-indigo-300 hover:underline">
            ← Retour aux parcours
        </a>

        <p class="text-sm text-cyan-300">{{ $chapter->learningPath->title }}</p>
        <h1 class="mt-2 text-3xl font-bold">{{ $chapter->title }}</h1>
        <p class="mt-2 text-slate-400">Rédige la leçon et configure les QCM qui débloquent la suite du cours.</p>

        @if (session('success'))
            <div class="mt-5 rounded-lg border border-green-500/20 bg-green-500/10 p-4 text-green-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save" class="mt-8 space-y-6">
            <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <label for="course-content" class="mb-2 block text-lg font-semibold">Leçon du chapitre</label>
                <p class="mb-3 text-sm text-slate-400">
                    Texte brut accepté. Utilise des retours à la ligne et des exemples de code pour structurer le cours.
                </p>
                <textarea id="course-content" wire:model="courseContent" rows="18" maxlength="30000" required
                          class="w-full rounded-xl border border-slate-700 bg-slate-950 p-4 font-mono text-sm leading-6 text-slate-100"></textarea>
                @error('courseContent') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold">QCM du chapitre</h2>
                        <p class="mt-1 text-sm text-slate-400">Quatre choix par question, une seule bonne réponse.</p>
                    </div>
                    <button type="button" wire:click="addQuestion" @disabled(count($questions) >= 25)
                            class="rounded-lg bg-slate-700 px-4 py-2 text-sm hover:bg-slate-600 disabled:opacity-50">
                        Ajouter un QCM
                    </button>
                </div>
                @error('questions') <p class="mb-4 text-sm text-red-400">{{ $message }}</p> @enderror

                @forelse ($questions as $index => $question)
                    <fieldset wire:key="chapter-question-{{ $question['id'] ?? 'new-'.$index }}"
                              class="mb-5 rounded-xl border border-slate-700 bg-slate-950/60 p-5">
                        <legend class="px-2 font-semibold text-indigo-200">Question {{ $index + 1 }}</legend>
                        @if (isset($question['id']))
                            <input type="hidden" wire:model="questions.{{ $index }}.id">
                        @endif

                        <div>
                            <label for="question-prompt-{{ $index }}" class="mb-2 block text-sm text-slate-300">Énoncé</label>
                            <textarea id="question-prompt-{{ $index }}" wire:model="questions.{{ $index }}.prompt"
                                      rows="2" maxlength="1000" required
                                      class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-white"></textarea>
                            @error("questions.$index.prompt") <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $optionKey => $optionLabel)
                                <div>
                                    <label for="question-{{ $index }}-option-{{ $optionKey }}" class="mb-2 block text-sm text-slate-300">
                                        Choix {{ $optionLabel }}
                                    </label>
                                    <input id="question-{{ $index }}-option-{{ $optionKey }}" type="text"
                                           wire:model="questions.{{ $index }}.options.{{ $optionKey }}"
                                           maxlength="500" required
                                           class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-white">
                                    @error("questions.$index.options.$optionKey")
                                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="question-{{ $index }}-correct" class="mb-2 block text-sm text-slate-300">Bonne réponse</label>
                                <select id="question-{{ $index }}-correct" wire:model="questions.{{ $index }}.correct_option"
                                        class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-white">
                                    <option value="a">A</option>
                                    <option value="b">B</option>
                                    <option value="c">C</option>
                                    <option value="d">D</option>
                                </select>
                            </div>
                            <div>
                                <label for="question-{{ $index }}-xp" class="mb-2 block text-sm text-slate-300">XP à gagner</label>
                                <input id="question-{{ $index }}-xp" type="number" min="1" max="100"
                                       wire:model="questions.{{ $index }}.xp_reward" required
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-white">
                                @error("questions.$index.xp_reward")
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="question-{{ $index }}-explanation" class="mb-2 block text-sm text-slate-300">
                                Explication après réussite (facultative)
                            </label>
                            <textarea id="question-{{ $index }}-explanation"
                                      wire:model="questions.{{ $index }}.explanation" rows="2" maxlength="2000"
                                      class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-white"></textarea>
                            @error("questions.$index.explanation")
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            @if (($question['completion_count'] ?? 0) > 0)
                                <p class="text-xs text-slate-400">
                                    Déjà réussi par {{ $question['completion_count'] }} étudiant(s); ce QCM ne peut pas être supprimé.
                                </p>
                            @else
                                <span></span>
                            @endif
                            <button type="button" wire:click="removeQuestion({{ $index }})"
                                    @disabled(($question['completion_count'] ?? 0) > 0)
                                    class="text-sm text-red-300 hover:text-red-200 disabled:cursor-not-allowed disabled:opacity-50">
                                Supprimer le QCM
                            </button>
                        </div>
                    </fieldset>
                @empty
                    <p class="rounded-lg border border-dashed border-slate-700 p-5 text-sm text-slate-400">
                        Ajoute au moins un QCM pour permettre aux étudiants de terminer ce chapitre.
                    </p>
                @endforelse
            </section>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-6 py-3 font-semibold hover:bg-indigo-500">
                Enregistrer le cours
            </button>
        </form>
    </div>
</div>
