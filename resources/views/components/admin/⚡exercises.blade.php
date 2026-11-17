
<?php

use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $difficulty = 'easy';
    public int $xp_reward = 10;
    public string $starter_code = '';
    public int $category_id = 0;
    public string $language = 'javascript';
    public bool $is_published = false;
    public int $sort_order = 0;

    public function save()
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard,expert'],
            'xp_reward' => ['required', 'integer', 'min:1'],
            'starter_code' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'language' => ['required', 'string', 'max:50'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

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

        if ($this->editingId) {
            Exercise::findOrFail($this->editingId)->update($validated);
            session()->flash('success', 'Exercice modifié avec succès.');
        } else {
            Exercise::create($validated);
            session()->flash('success', 'Exercice ajouté avec succès.');
        }

        $this->resetForm();
    }

    public function edit(int $id)
    {
        $exercise = Exercise::findOrFail($id);

        $this->editingId = $exercise->id;
        $this->title = $exercise->title;
        $this->description = $exercise->description;
        $this->difficulty = $exercise->difficulty;
        $this->xp_reward = $exercise->xp_reward;
        $this->starter_code = $exercise->starter_code ?? '';
        $this->category_id = $exercise->category_id;
        $this->language = $exercise->language;
        $this->is_published = $exercise->is_published;
        $this->sort_order = $exercise->sort_order;
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
            'difficulty',
            'xp_reward',
            'starter_code',
            'category_id',
            'language',
            'is_published',
            'sort_order',
        ]);

        $this->difficulty = 'easy';
        $this->xp_reward = 10;
        $this->language = 'javascript';
        $this->is_published = false;
        $this->sort_order = 0;
    }

    public function render()
    {
        return view('components.admin.⚡exercises', [
            'categories' => Category::orderBy('sort_order')->get(),
            'exercises' => Exercise::with('category')
                ->withCount('submissions')
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
                                <td colspan="7" class="p-6 text-center text-slate-400">
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
