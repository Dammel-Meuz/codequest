
<?php

use App\Models\Category;
use App\Models\Exercise;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public string $category = '';

    public string $difficulty = '';

    public function render()
    {
        $exercises = Exercise::query()
            ->with('category')
            ->withExists([
                'completions as is_completed' => function ($query) {
                    $query->where('user_id', auth()->id());
                }
            ])
            ->withCount('prerequisites')
            ->withCount([
                'prerequisites as completed_prerequisites_count' => fn ($query) => $query
                    ->whereHas('completions', fn ($completions) => $completions
                        ->where('user_id', auth()->id())),
            ])
            ->where('is_published', true)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->when($this->category, function ($query) {
                $query->where('category_id', $this->category);
            })
            ->when($this->difficulty, function ($query) {
                $query->where('difficulty', $this->difficulty);
            })
            ->orderBy('sort_order')
            ->paginate(9);

        return view('components.student.⚡exercises', [
            'exercises' => $exercises,
            'currentLevelNumber' => auth()->user()->currentLevelNumber(),
            'categories' => Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">

        <div class="mb-10">
            <h1 class="text-3xl font-bold sm:text-4xl">
                Catalogue des exercices
            </h1>
            <p class="mt-3 text-slate-400">
                Choisis un exercice et améliore tes compétences en JavaScript.
            </p>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-300">
                    Rechercher
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nom de l'exercice..."
                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none focus:border-indigo-500"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-300">
                    Catégorie
                </label>
                <select
                    wire:model.live="category"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none focus:border-indigo-500"
                >
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-300">
                    Difficulté
                </label>
                <select
                    wire:model.live="difficulty"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none focus:border-indigo-500"
                >
                    <option value="">Toutes les difficultés</option>
                    <option value="easy">Facile</option>
                    <option value="medium">Moyen</option>
                    <option value="hard">Difficile</option>
                    <option value="expert">Expert</option>
                </select>
            </div>

        </div>

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-xl font-semibold">Les exercices disponibles</h2>
            <span class="text-sm text-slate-400">
                {{ $exercises->total() }} exercice(s)
            </span>
        </div>

        @if ($exercises->isEmpty())

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-10 text-center">
                <h3 class="text-lg font-semibold">Aucun exercice trouvé</h3>
                <p class="mt-2 text-slate-400">
                    Essaie de modifier tes filtres de recherche.
                </p>
            </div>

        @else

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                @foreach ($exercises as $exercise)
                    @php
                        $isLevelUnlocked = $currentLevelNumber >= $exercise->requiredLevelNumber();
                        $hasCompletedPrerequisites = (int) $exercise->prerequisites_count
                            === (int) $exercise->completed_prerequisites_count;
                        $isUnlocked = (bool) $exercise->is_completed
                            || ($isLevelUnlocked && $hasCompletedPrerequisites);
                    @endphp

                    <div @class([
                        'rounded-2xl border bg-slate-900 p-6 transition',
                        'border-slate-800 hover:border-indigo-500' => $isUnlocked,
                        'border-amber-500/30 opacity-80' => ! $isUnlocked,
                    ])>

                       
                        <div class="mb-4 flex items-center justify-between">
                            <span class="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-400">
                                {{ $exercise->category->name }}
                            </span>

                            <div class="flex items-center gap-2">
                                @if (! $isUnlocked)
                                    <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
                                        @if (! $isLevelUnlocked)
                                            Verrouillé — niveau {{ $exercise->requiredLevelNumber() }}
                                        @else
                                            Verrouillé — prérequis
                                        @endif
                                    </span>
                                @elseif ($exercise->is_completed)
                                    <span class="rounded-full bg-green-500/10 px-3 py-1 text-xs font-medium text-green-400">
                                        Terminé
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-700 px-3 py-1 text-xs font-medium text-slate-300">
                                        À faire
                                    </span>
                                @endif

                                <span class="text-sm font-semibold text-yellow-400">
                                    +{{ $exercise->xp_reward }} XP
                                </span>
                            </div>
                        </div>


                        <h3 class="text-lg font-bold">
                            {{ $exercise->title }}
                        </h3>

                        <p class="mt-3 min-h-16 text-sm leading-6 text-slate-400">
                            {{ Str::limit($exercise->description, 110) }}
                        </p>

                        <div class="mt-5 flex items-center justify-between">
                            <span class="text-xs text-slate-400">
                                @switch($exercise->difficulty)
                                    @case('easy')
                                        Facile
                                        @break
                                    @case('medium')
                                        Moyen
                                        @break
                                    @case('hard')
                                        Difficile
                                        @break
                                    @case('expert')
                                        Expert
                                        @break
                                @endswitch
                            </span>

                            @if ($isUnlocked)
                                <a href="{{ route('student.exercise-show', $exercise->id) }}"
                                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                    Résoudre l'exercice
                                </a>
                            @else
                                <span class="rounded-lg bg-slate-800 px-4 py-2 text-xs text-slate-400">
                                    @if (! $isLevelUnlocked)
                                        Atteins le niveau {{ $exercise->requiredLevelNumber() }}
                                    @else
                                        Réussis les exercices prérequis
                                    @endif
                                </span>
                            @endif
                        </div>

                    </div>

                @endforeach

            </div>

            <div class="mt-8">
                {{ $exercises->links() }}
            </div>

        @endif

    </div>
</div>
