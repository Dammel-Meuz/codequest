<?php

namespace App\Jobs;

use App\Models\ExerciseCompletion;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\GamificationService;
use App\Services\JavaScriptSandbox;
use App\Services\WeeklyQuestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessExerciseSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30];

    public int $submissionId;

    public function __construct(int $submissionId)
    {
        $this->submissionId = $submissionId;
    }

    public function handle(): void
    {
        $startedAt = hrtime(true);
        $submission = Submission::find($this->submissionId);

        if (! $submission) {
            return;
        }

        if ($submission->status === 'accepted') {
            return;
        }

        $submission->update([
            'status' => 'processing',
            'processed_at' => null,
        ]);

        try {
            $testCases = $submission->exercise
                ->testCases()
                ->orderBy('sort_order')
                ->get();

            if ($testCases->isEmpty()) {
                $submission->update([
                    'status' => 'error',
                    'result' => [
                        'message' => 'Cet exercice ne contient aucun cas de test.',
                    ],
                    'passed_tests' => 0,
                    'total_tests' => 0,
                    'execution_time_ms' => $this->elapsedMilliseconds($startedAt),
                    'processed_at' => now(),
                ]);

                Log::warning('Exercise submission has no test cases', [
                    'submission_id' => $submission->id,
                    'exercise_id' => $submission->exercise_id,
                ]);

                return;
            }

            $deadline = microtime(true) + (float) config('code_execution.submission_timeout', 45);
            $tests = [];

            foreach ($testCases as $index => $testCase) {
                $remainingSeconds = $deadline - microtime(true);
                $execution = app(JavaScriptSandbox::class)->run(
                    $submission->source_code,
                    $testCase->input_data,
                    $remainingSeconds
                );
                $actual = $execution['actual'];
                $error = $execution['error'] ?? null;
                $passed = $error === null
                    && $this->sameValue($actual, $testCase->expected_output);

                $test = [
                    'test_index' => $index + 1,
                    'passed' => $passed,
                    'input' => $testCase->input_data,
                    'expected' => $testCase->expected_output,
                    'actual' => $actual,
                    'hidden' => (bool) $testCase->is_hidden,
                ];

                if ($error !== null) {
                    $test['error'] = $error;
                }

                if ($test['hidden']) {
                    unset($test['input'], $test['expected'], $test['actual'], $test['error']);
                }

                $tests[] = $test;
            }

            $passedTests = collect($tests)->where('passed', true)->count();
            $totalTests = count($tests);
            $executionTimeMs = $this->elapsedMilliseconds($startedAt);
            $decoded = [
                'passed_tests' => $passedTests,
                'total_tests' => $totalTests,
                'tests' => $tests,
            ];
            $status = $passedTests === $totalTests ? 'accepted' : 'rejected';

            DB::transaction(function () use (
                $submission,
                $passedTests,
                $totalTests,
                $status,
                $decoded,
                $executionTimeMs
            ) {
                $submission->update([
                    'status' => $status,
                    'result' => $decoded,
                    'passed_tests' => $passedTests,
                    'total_tests' => $totalTests,
                    'execution_time_ms' => $executionTimeMs,
                    'processed_at' => now(),
                ]);

                if ($status !== 'accepted') {
                    return;
                }

                $exercise = $submission->exercise;
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($submission->user_id);

                $completion = ExerciseCompletion::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'exercise_id' => $exercise->id,
                    ],
                    [
                        'submission_id' => $submission->id,
                        'xp_earned' => $exercise->xp_reward,
                        'completed_at' => now(),
                    ]
                );

                if ($completion->wasRecentlyCreated) {
                    XpTransaction::create([
                        'user_id' => $user->id,
                        'submission_id' => $submission->id,
                        'amount' => $exercise->xp_reward,
                        'reason' => 'exercise_completed',
                        'description' => 'Récompense pour l’exercice « '.$exercise->title.' »',
                    ]);

                    $user->increment('total_xp', $exercise->xp_reward);
                    app(WeeklyQuestService::class)->currentProgressFor($user);
                }

                app(GamificationService::class)->awardEligibleBadges($user->fresh());
            }, 3);

            Log::info('Exercise submission correction completed', [
                'submission_id' => $submission->id,
                'exercise_id' => $submission->exercise_id,
                'status' => $status,
                'passed_tests' => $passedTests,
                'total_tests' => $totalTests,
                'execution_time_ms' => $executionTimeMs,
            ]);
        } catch (Throwable $exception) {
            Log::error('Exercise submission correction failed', [
                'submission_id' => $submission->id,
                'exercise_id' => $submission->exercise_id,
                'execution_time_ms' => $this->elapsedMilliseconds($startedAt),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function sameValue(mixed $actual, mixed $expected): bool
    {
        return get_debug_type($actual) === get_debug_type($expected)
            && $this->normalizeValue($actual) === $this->normalizeValue($expected);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeValue($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeValue($item);
        }

        return $value;
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            Log::error('Exercise submission job exhausted its retries', [
                'submission_id' => $this->submissionId,
                'exception' => $exception,
            ]);
        }

        Submission::query()
            ->whereKey($this->submissionId)
            ->whereNotIn('status', ['accepted', 'rejected'])
            ->update([
                'status' => 'error',
                'result' => [
                    'message' => 'La correction n’a pas pu aboutir après plusieurs tentatives. Réessaie plus tard.',
                ],
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
