<?php

namespace App\Services;

use App\Models\ExerciseCompletion;
use App\Models\User;
use App\Models\WeeklyQuest;
use App\Models\WeeklyQuestProgress;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class WeeklyQuestService
{
    public function currentProgressFor(User $user): WeeklyQuestProgress
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $weekStart = $now->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->addWeek();
        $quest = WeeklyQuest::firstOrCreate(
            ['week_start' => $weekStart],
            [
                'title' => 'Défi de la semaine',
                'description' => 'Réussis 3 exercices différents cette semaine et gagne 30 XP bonus.',
                'goal_count' => 3,
                'reward_xp' => 30,
            ]
        );

        return DB::transaction(function () use ($user, $quest, $weekStart, $weekEnd) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $progress = WeeklyQuestProgress::query()
                ->where('weekly_quest_id', $quest->id)
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if (! $progress) {
                $progress = WeeklyQuestProgress::create([
                    'weekly_quest_id' => $quest->id,
                    'user_id' => $lockedUser->id,
                    'progress' => 0,
                ]);
            }

            $completedExercises = ExerciseCompletion::query()
                ->where('user_id', $lockedUser->id)
                ->where('completed_at', '>=', $weekStart)
                ->where('completed_at', '<', $weekEnd)
                ->orderBy('completed_at')
                ->orderBy('id')
                ->limit($quest->goal_count)
                ->get(['id', 'completed_at']);

            $progress->progress = $completedExercises->count();

            if ($progress->progress >= $quest->goal_count && ! $progress->completed_at) {
                $progress->completed_at = $completedExercises->last()->completed_at;
                $progress->save();

                XpTransaction::create([
                    'user_id' => $lockedUser->id,
                    'weekly_quest_progress_id' => $progress->id,
                    'amount' => $quest->reward_xp,
                    'reason' => 'weekly_quest_completed',
                    'description' => 'Quête hebdomadaire terminée : '.$quest->title,
                ]);

                $lockedUser->increment('total_xp', $quest->reward_xp);
                app(GamificationService::class)->awardEligibleBadges($lockedUser->fresh());
            } elseif ($progress->isDirty('progress')) {
                $progress->save();
            }

            return $progress->load('weeklyQuest');
        }, 3);
    }
}
