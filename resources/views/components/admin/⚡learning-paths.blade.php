<?php

use App\Models\LearningPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public int $sort_order = 0;
    public bool $is_published = false;
    public array $chapters = [];

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'chapters' => ['array', 'max:20'],
            'chapters.*.id' => ['nullable', 'integer'],
            'chapters.*.title' => ['required', 'string', 'max:255'],
            'chapters.*.description' => ['nullable', 'string', 'max:1000'],
            'chapters.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['title']);
        $slugExists = LearningPath::query()
            ->where('slug', $slug)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($slugExists) {
            $this->addError('title', 'Un parcours avec ce titre existe déjà.');
            return;
        }

        $chapterSlugs = collect($validated['chapters'])
            ->map(fn (array $chapter) => Str::slug($chapter['title']));

        if ($chapterSlugs->count() !== $chapterSlugs->unique()->count()) {
            $this->addError('chapters', 'Les titres des chapitres doivent être uniques dans un parcours.');
            return;
        }

        if ($this->editingId) {
            $keptChapterIds = collect($validated['chapters'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id);
            $chaptersWithCompletions = LearningPath::query()
                ->findOrFail($this->editingId)
                ->chapters()
                ->whereNotIn('id', $keptChapterIds)
                ->whereHas('quizQuestions.completions')
                ->exists();

            if ($chaptersWithCompletions) {
                $this->addError('chapters', 'Un chapitre contenant des QCM déjà réussis ne peut pas être supprimé.');
                return;
            }
        }

        DB::transaction(function () use ($validated, $slug) {
            $pathData = [
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'],
                'is_published' => $validated['is_published'] ?? false,
            ];

            if ($this->editingId) {
                $path = LearningPath::findOrFail($this->editingId);
                $path->update($pathData);
            } else {
                $path = LearningPath::create($pathData);
            }

            $keptChapterIds = [];

            foreach ($validated['chapters'] as $chapterData) {
                $chapter = isset($chapterData['id'])
                    ? $path->chapters()->whereKey($chapterData['id'])->firstOrFail()
                    : $path->chapters()->make();
                $chapter->fill([
                    'title' => $chapterData['title'],
                    'slug' => Str::slug($chapterData['title']),
                    'description' => $chapterData['description'] ?? null,
                    'sort_order' => $chapterData['sort_order'],
                ]);
                $path->chapters()->save($chapter);
                $keptChapterIds[] = $chapter->id;
            }

            $chaptersToRemove = $path->chapters();

            if ($keptChapterIds !== []) {
                $chaptersToRemove->whereNotIn('id', $keptChapterIds);
            }

            $chaptersToRemove->delete();
        });

        $this->resetForm();
        session()->flash('success', 'Parcours enregistré avec succès.');
    }

    public function addChapter(): void
    {
        if (count($this->chapters) >= 20) {
            $this->addError('chapters', 'Un parcours peut contenir au maximum 20 chapitres.');
            return;
        }

        $this->chapters[] = [
            'title' => '',
            'description' => '',
            'sort_order' => count($this->chapters),
        ];
        $this->resetValidation('chapters');
    }

    public function removeChapter(int $index): void
    {
        unset($this->chapters[$index]);
        $this->chapters = array_values($this->chapters);

        foreach ($this->chapters as $order => $chapter) {
            $this->chapters[$order]['sort_order'] = $order;
        }

        $this->resetValidation('chapters');
    }

    public function edit(int $id): void
    {
        $path = LearningPath::with('chapters')->findOrFail($id);
        $this->editingId = $path->id;
        $this->title = $path->title;
        $this->description = $path->description ?? '';
        $this->sort_order = $path->sort_order;
        $this->is_published = $path->is_published;
        $this->chapters = $path->chapters
            ->map(fn ($chapter) => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'description' => $chapter->description ?? '',
                'sort_order' => $chapter->sort_order,
            ])
            ->all();
    }

    public function delete(int $id): void
    {
        $path = LearningPath::findOrFail($id);

        if ($path->chapters()->whereHas('quizQuestions.completions')->exists()) {
            $this->addError('chapters', 'Ce parcours contient des QCM déjà réussis; il ne peut pas être supprimé.');
            return;
        }

        $path->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('success', 'Parcours supprimé. Les exercices associés ont été conservés.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'title',
            'description',
            'sort_order',
            'is_published',
            'chapters',
        ]);
        $this->sort_order = 0;
        $this->is_published = false;
        $this->chapters = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('components.admin.⚡learning-paths', [
            'paths' => LearningPath::query()
                ->withCount('chapters')
                ->with(['chapters' => fn ($query) => $query->withCount('exercises')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ])->layout('layouts.app');
    }
};

?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('admin.dashboard') }}" class="mb-6 inline-flex text-sm text-indigo-300 hover:underline">
            ← Retour à l’administration
        </a>

        <h1 class="text-3xl font-bold">Parcours d’apprentissage</h1>
        <p class="mt-2 text-slate-400">
            Organise les exercices en chapitres et contrôle leur ordre de progression.
        </p>

        @if (session('success'))
            <div class="mt-5 rounded-lg border border-green-500/20 bg-green-500/10 p-4 text-green-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="mb-5 text-xl font-semibold">
                {{ $editingId ? 'Modifier le parcours' : 'Créer un parcours' }}
            </h2>

            <form wire:submit="save" class="space-y-5">
                <div>
                    <label for="path-title" class="mb-2 block text-sm text-slate-300">Nom du parcours</label>
                    <input id="path-title" type="text" wire:model="title" maxlength="255" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                    @error('title') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="path-description" class="mb-2 block text-sm text-slate-300">Description</label>
                    <textarea id="path-description" wire:model="description" rows="3" maxlength="2000"
                              class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="path-order" class="mb-2 block text-sm text-slate-300">Ordre d’affichage</label>
                        <input id="path-order" type="number" min="0" wire:model="sort_order"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                        @error('sort_order') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-3 self-end pb-3 text-sm text-slate-300">
                        <input type="checkbox" wire:model="is_published" class="h-4 w-4 rounded">
                        Publier le parcours aux étudiants
                    </label>
                </div>

                <div class="rounded-xl border border-slate-700 p-4">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">Chapitres</h3>
                            <p class="mt-1 text-xs text-slate-400">Les exercices seront associés depuis leur formulaire.</p>
                        </div>
                        <button type="button" wire:click="addChapter" @disabled(count($chapters) >= 20)
                                class="rounded-lg bg-slate-700 px-4 py-2 text-sm hover:bg-slate-600 disabled:opacity-50">
                            Ajouter un chapitre
                        </button>
                    </div>

                    @error('chapters') <p class="mb-3 text-sm text-red-400">{{ $message }}</p> @enderror

                    @forelse ($chapters as $index => $chapter)
                        <fieldset wire:key="path-chapter-{{ $chapter['id'] ?? 'new-'.$index }}"
                                  class="mb-4 rounded-lg border border-slate-700 bg-slate-950/50 p-4">
                            <legend class="px-2 text-sm font-medium text-indigo-200">
                                Chapitre {{ $index + 1 }}
                            </legend>
                            @if (isset($chapter['id']))
                                <input type="hidden" wire:model="chapters.{{ $index }}.id">
                            @endif
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="chapter-title-{{ $index }}" class="mb-2 block text-sm text-slate-300">Titre</label>
                                    <input id="chapter-title-{{ $index }}" type="text"
                                           wire:model="chapters.{{ $index }}.title" maxlength="255" required
                                           class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                                    @error("chapters.$index.title") <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="chapter-order-{{ $index }}" class="mb-2 block text-sm text-slate-300">Ordre</label>
                                    <input id="chapter-order-{{ $index }}" type="number" min="0"
                                           wire:model="chapters.{{ $index }}.sort_order"
                                           class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white">
                                    @error("chapters.$index.sort_order") <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="chapter-description-{{ $index }}" class="mb-2 block text-sm text-slate-300">Description (facultative)</label>
                                <textarea id="chapter-description-{{ $index }}"
                                          wire:model="chapters.{{ $index }}.description" rows="2" maxlength="1000"
                                          class="w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-white"></textarea>
                                @error("chapters.$index.description") <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="removeChapter({{ $index }})"
                                    wire:confirm="Les exercices de ce chapitre seront conservés, mais détachés du parcours."
                                    class="mt-3 text-sm text-red-300 hover:text-red-200">
                                Supprimer le chapitre
                            </button>
                        </fieldset>
                    @empty
                        <p class="text-sm text-slate-400">Ajoute des chapitres pour structurer ce parcours.</p>
                    @endforelse
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-6 py-3 font-semibold hover:bg-indigo-500">
                        {{ $editingId ? 'Enregistrer les modifications' : 'Créer le parcours' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="resetForm"
                                class="rounded-lg bg-slate-700 px-6 py-3 font-semibold hover:bg-slate-600">
                            Annuler
                        </button>
                    @endif
                </div>
            </form>
        </section>

        <section class="mt-8 space-y-4">
            <h2 class="text-xl font-semibold">Parcours enregistrés</h2>
            @error('chapters') <p class="text-sm text-red-400" role="alert">{{ $message }}</p> @enderror
            @forelse ($paths as $path)
                <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $path->title }}</h3>
                                <span class="rounded-full px-2 py-1 text-xs {{ $path->is_published ? 'bg-green-500/10 text-green-300' : 'bg-slate-700 text-slate-300' }}">
                                    {{ $path->is_published ? 'Publié' : 'Brouillon' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-400">{{ $path->description }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ $path->chapters_count }} chapitre(s) · {{ $path->chapters->sum('exercises_count') }} exercice(s)
                            </p>
                            @if ($path->chapters->isNotEmpty())
                                <ul class="mt-3 space-y-2">
                                    @foreach ($path->chapters as $chapter)
                                        <li class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                            <span class="text-slate-300">{{ $chapter->title }}</span>
                                            <a href="{{ route('admin.chapter-course', $chapter) }}"
                                               class="text-indigo-300 hover:underline">
                                                Gérer le cours et les QCM
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="flex gap-3">
                            <button type="button" wire:click="edit({{ $path->id }})"
                                    class="text-sm text-indigo-300 hover:underline">Modifier</button>
                            <button type="button" wire:click="delete({{ $path->id }})"
                                    wire:confirm="Supprimer ce parcours ? Les exercices resteront disponibles."
                                    class="text-sm text-red-300 hover:underline">Supprimer</button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-slate-800 bg-slate-900 p-5 text-sm text-slate-400">
                    Aucun parcours n’a été créé.
                </p>
            @endforelse
        </section>
    </div>
</div>
