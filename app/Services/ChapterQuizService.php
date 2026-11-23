<?php

namespace App\Services;

use App\Models\ChapterQuizCompletion;
use App\Models\ChapterQuizQuestion;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;

class ChapterQuizService
{
    public function answer(User $user, ChapterQuizQuestion $question, string $selectedOption): array
    {
        if (! array_key_exists($selectedOption, $question->options ?? [])) {
            return [
                'correct' => false,
                'already_completed' => false,
                'xp_awarded' => 0,
            ];
        }

        if ($selectedOption !== $question->correct_option) {
            return [
                'correct' => false,
                'already_completed' => false,
                'xp_awarded' => 0,
            ];
        }

        $result = DB::transaction(function () use ($user, $question, $selectedOption) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $completion = ChapterQuizCompletion::firstOrCreate(
                [
                    'user_id' => $lockedUser->id,
                    'chapter_quiz_question_id' => $question->id,
                ],
                [
                    'selected_option' => $selectedOption,
                    'xp_earned' => $question->xp_reward,
                    'completed_at' => now(),
                ]
            );

            if (! $completion->wasRecentlyCreated) {
                return [
                    'correct' => true,
                    'already_completed' => true,
                    'xp_awarded' => 0,
                ];
            }

            XpTransaction::create([
                'user_id' => $lockedUser->id,
                'chapter_quiz_completion_id' => $completion->id,
                'amount' => $question->xp_reward,
                'reason' => 'quiz_question_completed',
                'description' => 'Réussite du QCM : '.$question->prompt,
            ]);

            $lockedUser->increment('total_xp', $question->xp_reward);

            return [
                'correct' => true,
                'already_completed' => false,
                'xp_awarded' => $question->xp_reward,
            ];
        }, 3);

        if (! $result['already_completed']) {
            app(GamificationService::class)->recordLearningActivity($user, now());
        }

        return $result;
    }
}
