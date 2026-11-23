
<?php

use App\Models\Category;
use App\Models\Exercise;
use App\Models\LearningPathChapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public array $hints = [];
    public string $success_explanation = '';
    public string $difficulty = 'easy';
    public int $xp_reward = 10;
    public string $starter_code = '';
    public string $chapter_id = '';
    public array $prerequisiteIds = [];
    public int $category_id = 0;
    public string $language = 'javascript';
    public bool $is_published = false;
    public int $sort_order = 0;
    public array $testCases = [];

    public function save()
    {
        $this->resetValidation();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'hints' => ['array', 'max:5'],
            'hints.*' => ['nullable', 'string', 'max:500'],
            'success_explanation' => ['nullable', 'string', 'max:5000'],
            'difficulty' => ['required', 'in:easy,medium,hard,expert'],
            'xp_reward' => ['required', 'integer', 'min:1'],
            'starter_code' => ['nullable', 'string'],
            'chapter_id' => ['nullable', 'integer', 'exists:learning_path_chapters,id'],
            'prerequisiteIds' => ['array', 'max:20'],
            'prerequisiteIds.*' => ['required', 'integer', 'distinct', 'exists:exercises,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'language' => ['required', 'string', 'max:50'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'testCases' => ['array', 'max:20'],
            'testCases.*.input' => ['required', 'string', 'max:10000'],
            'testCases.*.expected' => ['required', 'string', 'max:10000'],
            'testCases.*.hidden' => ['boolean'],
        ]);

        $prerequisiteIds = array_values(array_unique(array_map(
            'intval',
            $validated['prerequisiteIds'] ?? []
        )));

        if ($this->editingId && in_array($this->editingId, $prerequisiteIds, true)) {
            $this->addError('prerequisiteIds', 'Un exercice ne peut pas dépendre de lui-même.');
            return;
        }

        if ($prerequisiteIds !== [] && Exercise::query()
            ->whereIn('id', $prerequisiteIds)
            ->where('is_published', true)
            ->count() !== count($prerequisiteIds)) {
            $this->addError('prerequisiteIds', 'Les prérequis doivent être des exercices publiés.');
            return;
        }

        if ($this->editingId && $this->hasCircularPrerequisite($this->editingId, $prerequisiteIds)) {
            $this->addError('prerequisiteIds', 'Cette sélection créerait une boucle de prérequis.');
            return;
        }

        $validated['learning_path_chapter_id'] = ($validated['chapter_id'] ?? null) ?: null;
        unset($validated['chapter_id'], $validated['prerequisiteIds']);
        $validated['hints'] = $validated['hints'] ?? [];
        $validated['hints'] = array_values(array_filter(
            array_map(fn ($hint) => trim((string) $hint), $validated['hints']),
            fn ($hint) => $hint !== ''
        ));

        $testCases = [];

        foreach ($validated['testCases'] as $index => $testCase) {
            $input = json_decode($testCase['input'], true);
            $inputError = json_last_error();
            $expected = json_decode($testCase['expected'], true);
            $expectedError = json_last_error();

            if ($inputError !== JSON_ERROR_NONE) {
                $this->addError("testCases.$index.input", 'Les données d’entrée doivent être au format JSON valide.');
                return;
            }

            if ($expectedError !== JSON_ERROR_NONE) {
                $this->addError("testCases.$index.expected", 'Le résultat attendu doit être au format JSON valide.');
                return;
            }

            $testCases[] = [
                'input_data' => $input,
                'expected_output' => $expected,
                'is_hidden' => (bool) ($testCase['hidden'] ?? false),
                'sort_order' => count($testCases),
            ];
        }

        $slug = Str::slug($validated['title']);

        $slugExists = Exercise::where('slug', $slug)
            ->when($this->editingId, function ($query) {
                $query->where('id', '!=', $this->editingId);
            })
            ->exists();

        if ($slugExists) {
            $this->addError('title', 'Un exercice avec ce titre existe déjà.');
            return;
        }

        $validated['slug'] = $slug;

        $exercise = DB::transaction(function () use ($validated, $testCases, $prerequisiteIds) {
            if ($this->editingId) {
                $exercise = Exercise::findOrFail($this->editingId);
                $exercise->update($validated);
            } else {
                $exercise = Exercise::create($validated);
            }

            $exercise->testCases()->delete();

            foreach ($testCases as $testCase) {
                $exercise->testCases()->create($testCase);
            }
            $exercise->prerequisites()->sync($prerequisiteIds);

            return $exercise;
        });

        session()->flash(
            'success',
            $this->editingId
                ? 'Exercice modifié avec succès.'
                : 'Exercice ajouté avec succès.'
        );
        $this->resetForm();
    }

    public function addTestCase()
    {
        $this->testCases[] = [
            'input' => '[]',
            'expected' => 'null',
            'hidden' => false,
            'sort_order' => count($this->testCases),
        ];
    }

    public function removeTestCase(int $index)
    {
        unset($this->testCases[$index]);
        $this->testCases = array_values($this->testCases);
    }

    public function addHint(): void
    {
        if (count($this->hints) >= 5) {
            $this->addError('hints', 'Un exercice peut contenir au maximum 5 indices.');
            return;
        }

        $this->resetValidation('hints');
        $this->hints[] = '';
    }

    public function removeHint(int $index): void
    {
        unset($this->hints[$index]);
        $this->hints = array_values($this->hints);
        $this->resetValidation('hints');
    }

    private function hasCircularPrerequisite(int $exerciseId, array $prerequisiteIds): bool
    {
        $pending = $prerequisiteIds;
        $visited = [];

        while ($pending !== []) {
            $candidateId = (int) array_pop($pending);

            if ($candidateId === $exerciseId) {
                return true;
            }

            if (isset($visited[$candidateId])) {
                continue;
            }

            $visited[$candidateId] = true;
            $candidate = Exercise::find($candidateId);

            if ($candidate) {
                array_push(
                    $pending,
                    ...$candidate->prerequisites()->pluck('exercises.id')->map(fn ($id) => (int) $id)->all()
                );
            }
        }

        return false;
    }

    public function edit(int $id)
    {
        $exercise = Exercise::findOrFail($id);

        $this->editingId = $exercise->id;
        $this->title = $exercise->title;
        $this->description = $exercise->description;
        $this->hints = $exercise->hints ?? [];
        $this->success_explanation = $exercise->success_explanation ?? '';
        $this->difficulty = $exercise->difficulty;
        $this->xp_reward = $exercise->xp_reward;
        $this->starter_code = $exercise->starter_code ?? '';
        $this->chapter_id = (string) ($exercise->learning_path_chapter_id ?? '');
        $this->prerequisiteIds = $exercise->prerequisites()
            ->pluck('exercises.id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->category_id = $exercise->category_id;
        $this->language = $exercise->language;
        $this->is_published = $exercise->is_published;
        $this->sort_order = $exercise->sort_order;
        $this->testCases = $exercise->testCases()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($testCase) => [
                'input' => json_encode($testCase->input_data, JSON_UNESCAPED_UNICODE),
                'expected' => json_encode($testCase->expected_output, JSON_UNESCAPED_UNICODE),
                'hidden' => $testCase->is_hidden,
                'sort_order' => $testCase->sort_order,
            ])
            ->all();
    }

    public function togglePublication(int $id)
    {
        $exercise = Exercise::findOrFail($id);
        $exercise->update([
            'is_published' => !$exercise->is_published,
        ]);

        session()->flash('success', 'Statut de publication modifié.');
    }

    public function delete(int $id)
    {
        $exercise = Exercise::findOrFail($id);

        if ($exercise->submissions()->exists() ||
            $exercise->completions()->exists()) {
            session()->flash(
                'error',
                'Impossible de supprimer cet exercice : il possède déjà des soumissions ou des réussites.'
            );
            return;
        }

        $exercise->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('success', 'Exercice supprimé avec succès.');
    }

    public function resetForm()
    {
        $this->reset([
            'editingId',
            'title',
            'description',
            'hints',
            'success_explanation',
            'difficulty',
            'xp_reward',
            'starter_code',
            'chapter_id',
            'prerequisiteIds',
            'category_id',
            'language',
            'is_published',
            'sort_order',
            'testCases',
        ]);

        $this->difficulty = 'easy';
        $this->xp_reward = 10;
        $this->language = 'javascript';
        $this->chapter_id = '';
        $this->is_published = false;
        $this->sort_order = 0;
        $this->hints = [];
        $this->prerequisiteIds = [];
        $this->testCases = [];
    }

    public function render()
    {
        return view('components.admin.⚡exercises', [
            'categories' => Category::orderBy('sort_order')->get(),
            'chapters' => LearningPathChapter::with('learningPath')
                ->orderBy('learning_path_id')
                ->orderBy('sort_order')
                ->get(),
            'prerequisiteOptions' => Exercise::query()
                ->with('chapter.learningPath')
                ->where('is_published', true)
                ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                ->orderBy('title')
                ->get(),
            'exercises' => Exercise::with('category')
                ->withCount(['submissions', 'testCases'])
                ->orderBy('sort_order')
                ->latest()
                ->get(),
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white p-6">
    <div class="max-w-7xl mx-auto space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Gestion des exercices</h1>
                <p class="text-slate-400 mt-2">
                    Crée, modifie et gère les exercices de CodeQuest.
                </p>
            </div>

            <a href="{{ route('admin.dashboard') }}"
               class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700">
                ← Retour au tableau de bord
            </a>
        </div>

        @if (session()->has('success'))
            <div class="rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 p-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 p-4">
                {{ session('error') }}
            </div>
        @endif

        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-xl font-semibold mb-6">
                {{ $editingId ? 'Modifier un exercice' : 'Ajouter un exercice' }}
            </h2>

            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="block text-sm text-slate-300 mb-2">Titre</label>
                    <input type="text" wire:model="title"
                           class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                    @error('title') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-slate-300 mb-2">Description / Énoncé</label>
                    <textarea wire:model="description" rows="5"
                              class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white"></textarea>
                    @error('description') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl border border-slate-700 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">Indices progressifs</h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Ajoute jusqu’à 5 indices, révélés un par un à la demande de l’étudiant.
                            </p>
                        </div>

                        <button type="button" wire:click="addHint" @disabled(count($hints) >= 5)
                                class="rounded-lg bg-slate-700 px-4 py-2 text-sm hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-50">
                            Ajouter un indice
                        </button>
                    </div>

                    @error('hints') <p class="mb-3 text-sm text-red-400">{{ $message }}</p> @enderror

                    @forelse ($hints as $index => $hint)
                        <div wire:key="exercise-hint-{{ $index }}" class="mb-3 flex items-start gap-3">
                            <div class="flex-1">
                                <label for="exercise-hint-{{ $index }}" class="mb-2 block text-sm text-slate-300">
                                    Indice {{ $index + 1 }}
                                </label>
                                <textarea id="exercise-hint-{{ $index }}" wire:model="hints.{{ $index }}" rows="2"
                                          maxlength="500"
                                          class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white"></textarea>
                                @error("hints.$index")
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" wire:click="removeHint({{ $index }})"
                                    aria-label="Supprimer l’indice {{ $index + 1 }}"
                                    class="mt-8 text-sm text-red-400 hover:text-red-300">
                                Supprimer
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Aucun indice configuré.</p>
                    @endforelse
                </div>

                <div>
                    <label class="mb-2 block text-sm text-slate-300">Explication après réussite (facultative)</label>
                    <textarea wire:model="success_explanation" rows="4" maxlength="5000"
                              class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white"
                              placeholder="Récapitule le concept appris et pourquoi la solution fonctionne."></textarea>
                    @error('success_explanation')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Catégorie</label>
                        <select wire:model="category_id"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                            <option value="0">Choisir une catégorie</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Difficulté</label>
                        <select wire:model="difficulty"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                            <option value="easy">Facile</option>
                            <option value="medium">Moyen</option>
                            <option value="hard">Difficile</option>
                            <option value="expert">Expert</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-400">
                            Déblocage étudiant : facile dès le départ, moyen au niveau 2, difficile au niveau 4 et expert au niveau 6.
                        </p>
                        @error('difficulty') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Récompense XP</label>
                        <input type="number" min="1" wire:model="xp_reward"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                        @error('xp_reward') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Ordre d'affichage</label>
                        <input type="number" min="0" wire:model="sort_order"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                        @error('sort_order') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Langage</label>
                        <input type="text" wire:model="language"
                               class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white">
                        @error('language') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-300 mb-2">Code de départ (facultatif)</label>
                    <textarea wire:model="starter_code" rows="6"
                              class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-white font-mono text-sm"
                              placeholder="// Écris ici le code de départ..."></textarea>
                    @error('starter_code') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="exercise-chapter" class="mb-2 block text-sm text-slate-300">Parcours et chapitre (facultatif)</label>
                        <select id="exercise-chapter" wire:model="chapter_id"
                                class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                            <option value="">Exercice hors parcours</option>
                            @foreach ($chapters as $chapter)
                                <option value="{{ $chapter->id }}">
                                    {{ $chapter->learningPath->title }} — {{ $chapter->title }}
                                    @unless ($chapter->learningPath->is_published) (brouillon) @endunless
                                </option>
                            @endforeach
                        </select>
                        @error('chapter_id') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="exercise-prerequisites" class="mb-2 block text-sm text-slate-300">Exercices prérequis</label>
                        <select id="exercise-prerequisites" wire:model="prerequisiteIds" multiple size="5"
                                class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                            @foreach ($prerequisiteOptions as $prerequisite)
                                <option value="{{ $prerequisite->id }}">
                                    {{ $prerequisite->title }}
                                    @if ($prerequisite->chapter)
                                        — {{ $prerequisite->chapter->learningPath->title }} / {{ $prerequisite->chapter->title }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">
                            L’étudiant devra réussir chaque exercice sélectionné. Maintiens Ctrl pour plusieurs choix.
                        </p>
                        @error('prerequisiteIds') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        @error('prerequisiteIds.*') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-slate-700 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">Cas de test</h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Utilise du JSON. Pour une fonction nommée solution, utilise par exemple {"args":[2,3],"function":"solution"}.
                            </p>
                        </div>

                        <button type="button" wire:click="addTestCase"
                                class="rounded-lg bg-slate-700 px-4 py-2 text-sm hover:bg-slate-600">
                            Ajouter un test
                        </button>
                    </div>

                    @forelse ($testCases as $index => $testCase)
                        <div wire:key="test-case-{{ $index }}" class="mb-4 rounded-lg border border-slate-700 bg-slate-950/40 p-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm text-slate-300">Entrée JSON</label>
                                    <textarea rows="3" wire:model="testCases.{{ $index }}.input"
                                              class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 font-mono text-sm text-white"></textarea>
                                    @error("testCases.$index.input")
                                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm text-slate-300">Résultat attendu JSON</label>
                                    <textarea rows="3" wire:model="testCases.{{ $index }}.expected"
                                              class="w-full rounded-lg border border-slate-700 bg-slate-900 p-3 font-mono text-sm text-white"></textarea>
                                    @error("testCases.$index.expected")
                                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm text-slate-300">
                                    <input type="checkbox" wire:model="testCases.{{ $index }}.hidden"
                                           class="h-4 w-4 rounded">
                                    Test caché
                                </label>

                                <button type="button" wire:click="removeTestCase({{ $index }})"
                                        class="text-sm text-red-400 hover:text-red-300">
                                    Supprimer ce test
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">
                            Aucun cas de test. Les soumissions resteront en erreur jusqu’à l’ajout d’un test.
                        </p>
                    @endforelse
                </div>

                <label class="flex items-center gap-3 text-slate-300">
                    <input type="checkbox" wire:model="is_published"
                           class="w-4 h-4 rounded">
                    Publier cet exercice immédiatement
                </label>

                <div class="flex flex-wrap gap-3">
                    <button type="submit"
                            class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold">
                        {{ $editingId ? 'Enregistrer les modifications' : 'Ajouter l’exercice' }}
                    </button>

                    @if ($editingId)
                        <button type="button" wire:click="resetForm"
                                class="px-6 py-3 rounded-lg bg-slate-700 hover:bg-slate-600">
                            Annuler
                        </button>
                    @endif
                </div>
            </form>
        </section>

        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-xl font-semibold mb-6">Liste des exercices</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="p-3">Exercice</th>
                            <th class="p-3">Catégorie</th>
                            <th class="p-3">Difficulté</th>
                            <th class="p-3">XP</th>
                            <th class="p-3">Tests</th>
                            <th class="p-3">Soumissions</th>
                            <th class="p-3">Statut</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800">
                        @forelse ($exercises as $exercise)
                            <tr>
                                <td class="p-3 font-medium">{{ $exercise->title }}</td>
                                <td class="p-3 text-slate-300">{{ $exercise->category->name }}</td>
                                <td class="p-3">{{ $exercise->difficulty }}</td>
                                <td class="p-3">{{ $exercise->xp_reward }} XP</td>
                                <td class="p-3">{{ $exercise->test_cases_count }}</td>
                                <td class="p-3">{{ $exercise->submissions_count }}</td>
                                <td class="p-3">
                                    @if ($exercise->is_published)
                                        <span class="text-green-400">Publié</span>
                                    @else
                                        <span class="text-yellow-400">Brouillon</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button wire:click="edit({{ $exercise->id }})"
                                                class="text-indigo-400 hover:text-indigo-300">
                                            Modifier
                                        </button>

                                        <button wire:click="togglePublication({{ $exercise->id }})"
                                                wire:confirm="Confirmer le changement de publication ?"
                                                class="text-yellow-400 hover:text-yellow-300">
                                            {{ $exercise->is_published ? 'Dépublier' : 'Publier' }}
                                        </button>

                                        <button wire:click="delete({{ $exercise->id }})"
                                                wire:confirm="Voulez-vous vraiment supprimer cet exercice ?"
                                                class="text-red-400 hover:text-red-300">
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-6 text-center text-slate-400">
                                    Aucun exercice enregistré pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>
