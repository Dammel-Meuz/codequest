<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public User $student;

    public function mount(User $user): void
    {
        abort_unless($user->role === 'student', 404);

        $this->student = $user;
    }

    public function render()
    {
        $streak = $this->student->learningStreak;
        $currentStreak = $streak
            && $streak->last_activity_date
            && $streak->last_activity_date->gte(today()->subDay())
                ? $streak->current_streak
                : 0;

        return view('components.student.⚡public-profile', [
            'level' => $this->student->currentLevel(),
            'completedExercises' => $this->student->exerciseCompletions()->count(),
            'badges' => $this->student->badges()->orderByPivot('awarded_at', 'desc')->get(),
            'currentStreak' => $currentStreak,
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 px-4 py-10 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('leaderboard') }}"
           class="mb-8 inline-flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300">
            ← Retour au classement
        </a>

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-indigo-300">Profil étudiant</p>
                    <h1 class="mt-2 text-3xl font-bold">{{ $student->name }}</h1>
                    <p class="mt-2 text-slate-400">
                        {{ $level?->name ?? 'Débutant' }}
                        @if ($level)
                            · Niveau {{ $level->level_number }}
                        @endif
                    </p>
                </div>

                <div class="rounded-xl bg-yellow-500/10 px-5 py-4 text-right">
                    <p class="text-sm text-yellow-300">XP total</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-200">
                        {{ number_format($student->total_xp) }}
                    </p>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <p class="text-sm text-slate-400">Exercices réussis</p>
                    <p class="mt-2 text-2xl font-bold">{{ $completedExercises }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <p class="text-sm text-slate-400">Série actuelle</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ $currentStreak }} jour(s)
                    </p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-xl font-semibold">Badges obtenus</h2>

                @if ($badges->isEmpty())
                    <p class="mt-3 text-sm text-slate-400">
                        Aucun badge obtenu pour le moment.
                    </p>
                @else
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($badges as $badge)
                            <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-4">
                                <p class="font-semibold text-indigo-200">{{ $badge->name }}</p>
                                <p class="mt-1 text-sm text-slate-400">{{ $badge->description }}</p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Obtenu le {{ \Carbon\Carbon::parse($badge->pivot->awarded_at)->format('d/m/Y') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
