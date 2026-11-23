<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ChapterQuizQuestion;
use App\Models\LearningPath;
use App\Models\Submission;
use App\Models\User;
use App\Jobs\ProcessExerciseSubmission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_exercises_and_test_cases_seed_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'ndoyemamemoussa@gmail.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseCount('categories', 8);
        $this->assertDatabaseCount('levels', 10);
        $this->assertDatabaseCount('exercises', 9);
        $this->assertDatabaseCount('exercise_test_cases', 18);
        $this->assertDatabaseCount('badges', 7);
        $this->assertDatabaseCount('chapter_quiz_questions', 69);

        $this->assertDatabaseHas('exercises', [
            'slug' => 'additionner-deux-nombres',
            'is_published' => true,
        ]);

        $exercise = Exercise::where('slug', 'additionner-deux-nombres')->firstOrFail();
        $this->assertCount(2, $exercise->hints);
        $this->assertNotEmpty($exercise->success_explanation);
        $this->assertSame(
            ['function' => 'solution', 'args' => [2, 3]],
            $exercise->testCases()->orderBy('sort_order')->firstOrFail()->input_data
        );

        $this->assertSame(1, $exercise->testCases()->where('is_hidden', true)->count());

        $path = LearningPath::where('slug', 'bases-javascript')->firstOrFail();
        $this->assertSame(20, $path->chapters()->count());
        $this->assertSame(
            'Opérateurs',
            $exercise->fresh()->chapter->title
        );
        $this->assertSame(69, $path->chapters()->withCount('quizQuestions')->get()->sum('quiz_questions_count'));
        $this->assertTrue(
            ChapterQuizQuestion::query()->get()->every(fn (ChapterQuizQuestion $question) =>
                count($question->options) === 4
                && array_key_exists($question->correct_option, $question->options)
                && filled($question->explanation)
            )
        );
        $this->assertTrue(
            Exercise::where('slug', 'affichage-sur-le-console')
                ->firstOrFail()
                ->prerequisites
                ->contains('slug', 'afficher-un-message')
        );
    }

    public function test_demo_exercise_solutions_pass_their_seeded_tests(): void
    {
        $this->seed(DatabaseSeeder::class);

        $solutions = [
            'afficher-un-message' => 'console.log("Bonjour CodeQuest");',
            'affichage-sur-le-console' => 'console.log("Bonjour"); console.log("CodeQuest");',
            'additionner-deux-nombres' => 'function solution(a, b) { return a + b; }',
            'verifier-nombre-pair' => 'function solution(nombre) { return nombre % 2 === 0; }',
            'multiplier-par-deux' => 'function solution(nombre) { return nombre * 2; }',
            'saluer-une-personne' => 'function solution(prenom) { return `Bonjour, ${prenom} !`; }',
            'compter-les-caracteres' => 'function solution(texte) { return texte.length; }',
            'somme-d-un-tableau' => 'function solution(nombres) { return nombres.reduce((total, nombre) => total + nombre, 0); }',
            'inverser-une-chaine' => 'function solution(texte) { return [...texte].reverse().join(""); }',
        ];

        $user = User::where('email', 'test@example.com')->firstOrFail();

        foreach ($solutions as $slug => $sourceCode) {
            $exercise = Exercise::where('slug', $slug)->firstOrFail();
            $submission = Submission::create([
                'user_id' => $user->id,
                'exercise_id' => $exercise->id,
                'source_code' => $sourceCode,
                'status' => 'pending',
            ]);

            (new ProcessExerciseSubmission($submission->id))->handle();

            $this->assertSame('accepted', $submission->fresh()->status, $slug);
        }

        $this->assertSame(180, $user->fresh()->total_xp);
        $this->assertDatabaseCount('exercise_completions', 9);
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 30,
            'reason' => 'weekly_quest_completed',
        ]);
    }
}
