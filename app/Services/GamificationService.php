<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\LearningStreak;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    public function recordLearningActivity(User $user, DateTimeInterface $activityAt): LearningStreak
    {
        return DB::transaction(function () use ($user, $activityAt) {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $activityDate = CarbonImmutable::instance($activityAt)->startOfDay();
            $streak = LearningStreak::firstOrCreate(
                ['user_id' => $lockedUser->id],
                ['current_streak' => 0, 'longest_streak' => 0]
            );

            $lastActivityDate = $streak->last_activity_date?->toDateString();
            $date = $activityDate->toDateString();

            if ($lastActivityDate !== $date) {
                $streak->current_streak = $lastActivityDate === $activityDate->subDay()->toDateString()
                    ? $streak->current_streak + 1
                    : 1;
                $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
                $streak->last_activity_date = $date;
                $streak->save();
            }

            $this->awardEligibleBadges($lockedUser, $streak);

            return $streak;
        }, 3);
    }

    public function awardEligibleBadges(User $user, ?LearningStreak $streak = null): void
    {
        $metrics = [
            'completions' => $user->exerciseCompletions()->count(),
            'xp' => (int) $user->fresh()->total_xp,
            'streak' => $streak?->current_streak
                ?? $user->learningStreak()->value('current_streak')
                ?? 0,
        ];

        $eligibleBadges = Badge::query()
            ->where(function ($query) use ($metrics) {
                foreach ($metrics as $type => $value) {
                    $query->orWhere(function ($criteria) use ($type, $value) {
                        $criteria->where('criteria_type', $type)
                            ->where('criteria_value', '<=', $value);
                    });
                }
            })
            ->get();

        foreach ($eligibleBadges as $badge) {
            DB::table('user_badges')->insertOrIgnore([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'awarded_at' => now(),
            ]);
        }
    }
}
