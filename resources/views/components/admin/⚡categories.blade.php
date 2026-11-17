
<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $description = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public ?int $editingId = null;

    public function save()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['name']);

        $existingSlug = Category::where('slug', $slug)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($existingSlug) {
            $this->addError('name', 'Une catégorie avec ce nom existe déjà.');
            return;
        }

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update([
                ...$validated,
                'slug' => $slug,
            ]);
        } else {
            Category::create([
                ...$validated,
                'slug' => $slug,
            ]);
        }

        $this->resetForm();

        session()->flash('success', 'Catégorie enregistrée avec succès.');
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
    }

    public function toggleStatus(int $id): void
    {
        $category = Category::findOrFail($id);

        $category->update([
            'is_active' => !$category->is_active,
        ]);

        session()->flash('success', 'Statut de la catégorie modifié.');
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->exercises()->exists()) {
            session()->flash('error', 'Impossible de supprimer cette catégorie : elle contient des exercices.');
            return;
        }

        $category->delete();

        session()->flash('success', 'Catégorie supprimée avec succès.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'description',
            'sort_order',
            'is_active',
            'editingId',
        ]);

        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }

    public function render()
    {
        return view('components.admin.⚡categories', [
            'categories' => Category::withCount('exercises')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }
};

?>

<div class="min-h-screen bg-slate-950 px-6 py-10 text-white">
    <div class="mx-auto max-w-7xl">

        <h1 class="mb-2 text-3xl font-bold text-blue-400">
            Gestion des catégories
        </h1>

        <p class="mb-8 text-slate-400">
            Ajoute, modifie et organise les catégories de programmation.
        </p>

        @if(session('success'))
            <div class="mb-5 rounded-lg bg-green-900/40 p-4 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 rounded-lg bg-red-900/40 p-4 text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-10 rounded-xl border border-slate-800 bg-slate-900 p-6">

            <h2 class="mb-5 text-xl font-bold">
                {{ $editingId ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
            </h2>

            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="mb-2 block text-sm text-slate-300">
                        Nom de la catégorie
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Exemple : Variables et constantes"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm text-slate-300">
                        Description
                    </label>

                    <textarea
                        wire:model="description"
                        rows="3"
                        placeholder="Décris cette catégorie..."
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm text-slate-300">
                        Ordre d'affichage
                    </label>

                    <input
                        type="number"
                        min="0"
                        wire:model="sort_order"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white"
                    >

                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-slate-300">
                    <input type="checkbox" wire:model="is_active">
                    Catégorie active
                </label>

                <div class="flex flex-wrap gap-3">

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-3 font-semibold hover:bg-blue-700"
                    >
                        {{ $editingId ? 'Enregistrer les modifications' : 'Ajouter la catégorie' }}
                    </button>

                    @if($editingId)
                        <button
                            type="button"
                            wire:click="resetForm"
                            class="rounded-lg bg-slate-700 px-5 py-3 hover:bg-slate-600"
                        >
                            Annuler
                        </button>
                    @endif

                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 p-6">

            <h2 class="mb-5 text-xl font-bold">
                Catégories enregistrées
            </h2>

            <table class="w-full text-left">

                <thead class="border-b border-slate-700 text-sm text-slate-400">
                    <tr>
                        <th class="p-3">Nom</th>
                        <th class="p-3">Exercices</th>
                        <th class="p-3">Ordre</th>
                        <th class="p-3">Statut</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($categories as $category)
                        <tr class="border-b border-slate-800">

                            <td class="p-3">
                                <p class="font-semibold">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $category->slug }}</p>
                            </td>

                            <td class="p-3">
                                {{ $category->exercises_count }}
                            </td>

                            <td class="p-3">
                                {{ $category->sort_order }}
                            </td>

                            <td class="p-3">
                                @if($category->is_active)
                                    <span class="text-green-400">Active</span>
                                @else
                                    <span class="text-red-400">Inactive</span>
                                @endif
                            </td>

                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">

                                    <button
                                        wire:click="edit({{ $category->id }})"
                                        class="rounded bg-blue-600 px-3 py-2 text-sm hover:bg-blue-700"
                                    >
                                        Modifier
                                    </button>

                                    <button
                                        wire:click="toggleStatus({{ $category->id }})"
                                        class="rounded bg-yellow-600 px-3 py-2 text-sm hover:bg-yellow-700"
                                    >
                                        {{ $category->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>

                                    <button
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Es-tu sûr de vouloir supprimer cette catégorie ?"
                                        class="rounded bg-red-600 px-3 py-2 text-sm hover:bg-red-700"
                                    >
                                        Supprimer
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-5 text-center text-slate-400">
                                Aucune catégorie enregistrée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="mt-8">
            <a href="{{ route('admin.dashboard') }}"
               class="text-blue-400 hover:underline">
                ← Retour à l'administration
            </a>
        </div>

    </div>
</div>
