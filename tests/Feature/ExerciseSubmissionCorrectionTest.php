<?php

namespace Tests\Feature;

use App\Jobs\ProcessExerciseSubmission;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseTestCase;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExerciseSubmissionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_output_can_be_accepted_and_awards_xp_once(): void
    {
        $this->seed(BadgeSeeder::class);

        $category = Category::create([
            'name' => 'Tests',
            'slug' => 'tests',
            'is_active' => true,
        ]);

        $exercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Bonjour',
            'slug' => 'bonjour',
            'description' => 'Afficher Bonjour',
            'success_explanation' => 'console.log affiche le texte dans la console.',
            'difficulty' => 'easy',
            'xp_reward' => 20,
            'language' => 'javascript',
            'is_published' => true,
        ]);

        ExerciseTestCase::create([
            'exercise_id' => $exercise->id,
            'input_data' => [],
            'expected_output' => 'Bonjour',
            'is_hidden' => false,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'total_xp' => 0,
        ]);

        $submission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'console.log("Bonjour");',
            'status' => 'pending',
        ]);

        (new ProcessExerciseSubmission($submission->id))->handle();

        $submission->refresh();
        $user->refresh();

        $this->assertSame('accepted', $submission->status);
        $this->assertSame(1, $submission->passed_tests);
        $this->assertNotNull($submission->execution_time_ms);
        $this->assertSame(20, $user->total_xp);
        $this->assertDatabaseCount('exercise_completions', 1);
        $this->assertDatabaseCount('xp_transactions', 1);
        $this->assertDatabaseHas('exercise_completions', [
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'submission_id' => $submission->id,
            'xp_earned' => 20,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'submission_id' => $submission->id,
            'amount' => 20,
            'reason' => 'exercise_completed',
        ]);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => \App\Models\Badge::where('code', 'first_completion')->value('id'),
        ]);

        Livewire::actingAs($user)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertSee('Tu as gagné 20 XP')
            ->assertSee('À retenir')
            ->assertSee('console.log affiche le texte dans la console.')
            ->assertSee('Test 1 — réussi');

        (new ProcessExerciseSubmission($submission->id))->handle();

        $secondSubmission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'console.log("Bonjour");',
            'status' => 'pending',
        ]);

        (new ProcessExerciseSubmission($secondSubmission->id))->handle();

        $user->refresh();
        $this->assertSame(20, $user->total_xp);
        $this->assertDatabaseCount('exercise_completions', 1);
        $this->assertDatabaseCount('xp_transactions', 1);

        Livewire::actingAs($user)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertSee('ne rapporte pas d’XP supplémentaire');
    }

    public function test_failed_result_is_rejected(): void
    {
        $category = Category::create([
            'name' => 'Tests',
            'slug' => 'tests',
            'is_active' => true,
        ]);

        $exercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Addition',
            'slug' => 'addition',
            'description' => 'Retourner une somme',
            'hints' => [
                'Observe les deux paramètres reçus par la fonction.',
                'Additionne les valeurs et retourne le résultat.',
            ],
            'success_explanation' => 'Cette explication ne doit pas apparaître après un échec.',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'language' => 'javascript',
            'is_published' => true,
        ]);

        ExerciseTestCase::create([
            'exercise_id' => $exercise->id,
            'input_data' => ['args' => [2, 3]],
            'expected_output' => 5,
            'is_hidden' => false,
            'sort_order' => 0,
        ]);
        ExerciseTestCase::create([
            'exercise_id' => $exercise->id,
            'input_data' => [],
            'expected_output' => 'SECRET_EXPECTED_VALUE',
            'is_hidden' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'total_xp' => 0,
        ]);

        $submission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'function solution(a, b) { return a - b; }',
            'status' => 'pending',
        ]);

        (new ProcessExerciseSubmission($submission->id))->handle();

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame(0, $submission->passed_tests);
        $this->assertDatabaseCount('exercise_completions', 0);
        $this->assertDatabaseCount('xp_transactions', 0);

        Livewire::actingAs($user)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertDontSee('Additionne les valeurs et retourne le résultat.')
            ->call('revealNextHint')
            ->assertSee('Observe les deux paramètres reçus par la fonction.')
            ->assertDontSee('Additionne les valeurs et retourne le résultat.')
            ->call('revealNextHint')
            ->assertSee('Additionne les valeurs et retourne le résultat.')
            ->assertSee('Test 1 — échoué')
            ->assertSee('Test caché échoué.')
            ->assertDontSee('Cette explication ne doit pas apparaître après un échec.')
            ->assertDontSee('SECRET_EXPECTED_VALUE');
    }

    public function test_non_terminating_code_is_stopped_and_rejected(): void
    {
        $category = Category::create([
            'name' => 'Tests',
            'slug' => 'tests',
            'is_active' => true,
        ]);

        $exercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Boucle infinie',
            'slug' => 'boucle-infinie',
            'description' => 'Ne jamais terminer',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'language' => 'javascript',
            'is_published' => true,
        ]);

        ExerciseTestCase::create([
            'exercise_id' => $exercise->id,
            'input_data' => [],
            'expected_output' => 'unreachable',
            'is_hidden' => false,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create(['role' => 'student']);
        $submission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'while (true) {}',
            'status' => 'pending',
        ]);

        (new ProcessExerciseSubmission($submission->id))->handle();

        $submission->refresh();

        $this->assertSame('rejected', $submission->status);
        $this->assertSame(0, $submission->passed_tests);
        $this->assertNotEmpty($submission->result['tests'][0]['error']);
        $this->assertDatabaseCount('exercise_completions', 0);
    }

    public function test_exhausted_queue_job_marks_unfinished_submission_as_error(): void
    {
        $category = Category::create([
            'name' => 'Tests',
            'slug' => 'tests',
            'is_active' => true,
        ]);

        $exercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Queue',
            'slug' => 'queue',
            'description' => 'Test de queue',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'language' => 'javascript',
            'is_published' => true,
        ]);

        $user = User::factory()->create(['role' => 'student']);
        $submission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'console.log("Bonjour");',
            'status' => 'processing',
        ]);

        $job = new ProcessExerciseSubmission($submission->id);

        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->timeout);

        $job->failed(new \RuntimeException('Worker failed'));

        $submission->refresh();
        $this->assertSame('error', $submission->status);
        $this->assertSame(
            'La correction n’a pas pu aboutir après plusieurs tentatives. Réessaie plus tard.',
            $submission->result['message']
        );

        $submission->update(['status' => 'accepted']);
        $job->failed(new \RuntimeException('Late worker failure'));

        $this->assertSame('accepted', $submission->fresh()->status);
    }
}
