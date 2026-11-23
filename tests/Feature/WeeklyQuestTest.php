<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExerciseCompletion;
use App\Models\ExerciseTestCase;
use App\Models\Submission;
use App\Models\User;
use App\Jobs\ProcessExerciseSubmission;
use App\Services\WeeklyQuestService;
use App\Services\JavaScriptSandbox;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyQuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_week_starts_a_separate_quest_progress_record(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $service = app(WeeklyQuestService::class);

        try {
            Carbon::setTestNow(CarbonImmutable::parse('2026-11-16 00:01:00', config('app.timezone')));
            $firstWeekProgress = $service->currentProgressFor($student);

            Carbon::setTestNow(CarbonImmutable::parse('2026-11-23 00:01:00', config('app.timezone')));
            $nextWeekProgress = $service->currentProgressFor($student);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertNotSame($firstWeekProgress->weekly_quest_id, $nextWeekProgress->weekly_quest_id);
        $this->assertSame(0, $nextWeekProgress->progress);
        $this->assertDatabaseCount('weekly_quests', 2);
        $this->assertDatabaseCount('weekly_quest_progresses', 2);
    }

    public function test_a_successful_exercise_submission_updates_the_weekly_quest(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $exercise = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        ExerciseTestCase::create([
            'exercise_id' => $exercise->id,
            'input_data' => [],
            'expected_output' => 'Réussi',
            'is_hidden' => false,
            'sort_order' => 99,
        ]);
        $submission = Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'console.log("Réussi");',
            'status' => 'pending',
        ]);

        $this->mock(JavaScriptSandbox::class, function ($mock) {
            $mock->shouldReceive('run')
                ->twice()
                ->andReturn(
                    ['actual' => 'Bonjour CodeQuest'],
                    ['actual' => 'Réussi']
                );
        });

        (new ProcessExerciseSubmission($submission->id))->handle();

        $this->assertSame('accepted', $submission->fresh()->status);
        $this->assertSame(1, app(WeeklyQuestService::class)->currentProgressFor($student)->progress);
    }

    public function test_three_unique_exercises_this_week_award_the_bonus_xp_once(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $exercises = Exercise::query()->whereIn('slug', [
            'afficher-un-message',
            'additionner-deux-nombres',
            'verifier-nombre-pair',
            'multiplier-par-deux',
        ])->get()->keyBy('slug');
        $weekStart = CarbonImmutable::now(config('app.timezone'))->startOfWeek();
        $now = CarbonImmutable::now(config('app.timezone'));

        $this->completeExercise($student, $exercises['afficher-un-message'], $now);
        $this->completeExercise($student, $exercises['additionner-deux-nombres'], $now);
        $this->completeExercise($student, $exercises['verifier-nombre-pair'], $weekStart->subSecond());

        $service = app(WeeklyQuestService::class);
        $progress = $service->currentProgressFor($student);

        $this->assertSame(2, $progress->progress);
        $this->assertNull($progress->completed_at);
        $this->assertSame(0, $student->fresh()->total_xp);

        $this->completeExercise($student, $exercises['multiplier-par-deux'], $now);

        $progress = $service->currentProgressFor($student);
        $this->assertSame(3, $progress->progress);
        $this->assertNotNull($progress->completed_at);
        $this->assertSame(30, $student->fresh()->total_xp);
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $student->id,
            'weekly_quest_progress_id' => $progress->id,
            'amount' => 30,
            'reason' => 'weekly_quest_completed',
        ]);

        $service->currentProgressFor($student);

        $this->assertSame(30, $student->fresh()->total_xp);
        $this->assertDatabaseCount('xp_transactions', 1);
    }

    private function completeExercise(User $student, Exercise $exercise, CarbonImmutable $completedAt): void
    {
        $submission = Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'function solution() { return true; }',
            'status' => 'accepted',
        ]);

        ExerciseCompletion::create([
            'user_id' => $student->id,
            'exercise_id' => $exercise->id,
            'submission_id' => $submission->id,
            'xp_earned' => $exercise->xp_reward,
            'completed_at' => $completedAt,
        ]);
    }
}
