<?php

use App\Models\Category;
use App\Models\Level;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $period = 'overall';

    public string $categoryId = '';

    public function render()
    {
        $period = $this->period === 'weekly' ? 'weekly' : 'overall';
        $category = $this->categoryId !== ''
            ? Category::query()->where('is_active', true)->find($this->categoryId)
            : null;

        $usersQuery = User::query()
            ->where('users.role', 'student')
            ->select('users.id', 'users.name', 'users.total_xp')
            ->selectSub(
                Level::query()
                    ->select('name')
                    ->whereColumn('levels.min_xp', '<=', 'users.total_xp')
                    ->orderByDesc('levels.min_xp')
                    ->limit(1),
                'level_name'
            );

        if ($period === 'weekly' || $category) {
            $pointsQuery = XpTransaction::query()
                ->leftJoin('submissions', 'submissions.id', '=', 'xp_transactions.submission_id')
                ->leftJoin('exercises', 'exercises.id', '=', 'submissions.exercise_id')
                ->when($period === 'weekly', function ($query) {
                    $query->where('xp_transactions.created_at', '>=', now()->startOfWeek())
                        ->where('xp_transactions.created_at', '<', now()->startOfWeek()->addWeek());
                })
                ->when($category, fn ($query) => $query->where('exercises.category_id', $category->id))
                ->select('xp_transactions.user_id')
                ->selectRaw('SUM(xp_transactions.amount) as ranking_points')
                ->groupBy('xp_transactions.user_id')
                ->havingRaw('SUM(xp_transactions.amount) > 0');

            $usersQuery
                ->joinSub($pointsQuery, 'user_points', 'users.id', '=', 'user_points.user_id')
                ->addSelect('user_points.ranking_points');
        } else {
            $usersQuery->addSelect('users.total_xp as ranking_points');
        }

        $rankedUsers = DB::query()
            ->fromSub($usersQuery, 'leaderboard_users')
            ->select('leaderboard_users.*')
            ->selectRaw(
                'ROW_NUMBER() OVER (ORDER BY leaderboard_users.ranking_points DESC, leaderboard_users.name ASC, leaderboard_users.id ASC) AS rank_position'
            );

        $currentPosition = DB::query()
            ->fromSub(clone $rankedUsers, 'ranked_users')
            ->where('id', auth()->id())
            ->value('rank_position');

        $leaders = DB::query()
            ->fromSub($rankedUsers, 'ranked_users')
            ->where('rank_position', '<=', 100)
            ->orderBy('rank_position')
            ->get();

        return view('components.student.⚡leaderboard', [
            'leaders' => $leaders,
            'currentPosition' => $currentPosition,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'selectedCategory' => $category,
            'selectedPeriod' => $period,
            'weekStart' => now()->startOfWeek(),
            'weekEnd' => now()->startOfWeek()->addDays(6),
        ])->layout('layouts.app');
    }
};
?>

<div wire:poll.30s class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold sm:text-4xl">Classement CodeQuest</h1>
                <p class="mt-2 text-slate-400">
                    Compare ta progression avec les autres étudiants.
                </p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-slate-200 hover:bg-slate-700">
                Retour au tableau de bord
            </a>
        </div>

        <div class="mb-6 grid gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:grid-cols-2">
            <div>
                <label for="leaderboard-period" class="mb-2 block text-sm font-medium text-slate-300">
                    Période
                </label>
                <select id="leaderboard-period" wire:model.live="period"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                    <option value="overall">Général — XP total</option>
                    <option value="weekly">Cette semaine — XP gagnés</option>
                </select>
            </div>

            <div>
                <label for="leaderboard-category" class="mb-2 block text-sm font-medium text-slate-300">
                    Catégorie
                </label>
                <select id="leaderboard-category" wire:model.live="categoryId"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-6 rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-4 text-sm">
            @if ($currentPosition)
                Ta position dans ce classement :
                <strong class="text-indigo-300">#{{ $currentPosition }}</strong>
            @else
                Tu n’es pas encore classé ici. Gagne des XP dans cette période ou cette catégorie pour apparaître.
            @endif
            @if ($selectedPeriod === 'weekly')
                <span class="mt-1 block text-xs text-slate-400">
                    Semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}
                </span>
            @endif
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
            <div class="border-b border-slate-800 px-5 py-4">
                <h2 class="text-lg font-semibold">
                    @if ($selectedCategory)
                        {{ $selectedCategory->name }}
                    @else
                        Classement général
                    @endif
                    @if ($selectedPeriod === 'weekly')
                        <span class="text-slate-400">— cette semaine</span>
                    @endif
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Les 100 premiers étudiants, classés par XP décroissants.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Position</th>
                            <th class="px-5 py-3">Étudiant</th>
                            <th class="px-5 py-3">Niveau</th>
                            <th class="px-5 py-3 text-right">XP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($leaders as $leader)
                            <tr @class([
                                'transition hover:bg-slate-800/60',
                                'bg-indigo-500/5' => (int) $leader->id === auth()->id(),
                            ])>
                                <td class="px-5 py-4 font-bold text-slate-300">
                                    #{{ $leader->rank_position }}
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('student.public-profile', $leader->id) }}"
                                       class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                                        {{ $leader->name }}
                                    </a>
                                    @if ((int) $leader->id === auth()->id())
                                        <span class="ml-2 text-xs text-slate-400">Toi</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-300">
                                    {{ $leader->level_name ?? 'Débutant' }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-yellow-300">
                                    {{ number_format((int) $leader->ranking_points) }} XP
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                    Aucun étudiant n’a encore gagné d’XP dans cette sélection.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
