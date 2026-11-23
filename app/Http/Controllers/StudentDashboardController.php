<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Level;
use App\Models\Submission;
use App\Models\User;
use App\Services\WeeklyQuestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $weeklyQuestProgress = app(WeeklyQuestService::class)->currentProgressFor($user);
        $user->refresh();
        $streak = $user->learningStreak;
        $currentLevel = $user->currentLevel();
        $nextLevel = $user->nextLevel();
        $currentLevelNumber = $currentLevel?->level_number ?? 1;
        $currentXp = (int) $user->total_xp;

        if ($nextLevel && $currentLevel) {
            $levelXpRange = $nextLevel->min_xp - $currentLevel->min_xp;
            $levelXpProgress = $currentXp - $currentLevel->min_xp;
            $levelProgress = $levelXpRange > 0
                ? min(100, max(0, ($levelXpProgress / $levelXpRange) * 100))
                : 0;
        } else {
            $levelProgress = $currentLevel ? 100 : 0;
        }

        $leaderboardPosition = $user->role === 'student'
            ? User::query()
                ->where('role', 'student')
                ->where(function ($query) use ($user) {
                    $query->where('total_xp', '>', $user->total_xp)
                        ->orWhere(function ($tie) use ($user) {
                            $tie->where('total_xp', $user->total_xp)
                                ->where('name', '<', $user->name);
                        })
                        ->orWhere(function ($tie) use ($user) {
                            $tie->where('total_xp', $user->total_xp)
                                ->where('name', $user->name)
                                ->where('id', '<', $user->id);
                        });
                })
                ->count() + 1
            : null;

        $unlockedDifficulties = collect([
            'easy' => 1,
            'medium' => 2,
            'hard' => 4,
            'expert' => 6,
        ])->filter(fn (int $requiredLevel) => $requiredLevel <= $currentLevelNumber)
            ->keys()
            ->all();

        $startedExercises = Exercise::query()
            ->with('category')
            ->where('is_published', true)
            ->whereIn('difficulty', $unlockedDifficulties)
            ->whereHas('submissions', fn ($query) => $query->where('user_id', $user->id))
            ->whereDoesntHave('completions', fn ($query) => $query->where('user_id', $user->id))
            ->addSelect([
                'latest_submission_status' => Submission::query()
                    ->select('status')
                    ->whereColumn('submissions.exercise_id', 'exercises.id')
                    ->where('submissions.user_id', $user->id)
                    ->orderByDesc('submissions.id')
                    ->limit(1),
                'latest_submission_id' => Submission::query()
                    ->select('id')
                    ->whereColumn('submissions.exercise_id', 'exercises.id')
                    ->where('submissions.user_id', $user->id)
                    ->orderByDesc('submissions.id')
                    ->limit(1),
            ])
            ->orderByDesc('latest_submission_id')
            ->limit(5)
            ->get();

        $preferredDifficulty = match (true) {
            $currentLevelNumber >= 6 => 'expert',
            $currentLevelNumber >= 4 => 'hard',
            $currentLevelNumber >= 2 => 'medium',
            default => 'easy',
        };

        $recommendations = Exercise::query()
            ->with('category')
            ->where('is_published', true)
            ->availableFor($user)
            ->whereDoesntHave('completions', fn ($query) => $query->where('user_id', $user->id))
            ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id))
            ->orderByRaw('CASE difficulty WHEN ? THEN 0 ELSE 1 END', [$preferredDifficulty])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'currentLevel' => $currentLevel,
            'nextLevel' => $nextLevel,
            'currentXp' => $currentXp,
            'levelProgress' => $levelProgress,
            'completedCount' => $user->exerciseCompletions()->count(),
            'weeklyQuestProgress' => $weeklyQuestProgress,
            'leaderboardPosition' => $leaderboardPosition,
            'startedExercises' => $startedExercises,
            'recentSubmissions' => $user->submissions()
                ->with(['exercise', 'xpTransaction'])
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'badges' => $user->badges()
                ->orderByPivot('awarded_at', 'desc')
                ->limit(4)
                ->get(),
            'recommendations' => $recommendations,
            'activeStreak' => $streak
                && $streak->last_activity_date
                && $streak->last_activity_date->gte(today()->subDay())
                    ? $streak->current_streak
                    : 0,
        ]);
    }
}
