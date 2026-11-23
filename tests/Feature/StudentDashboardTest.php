<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Exercise;
use App\Models\ExerciseCompletion;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_centralizes_progress_activity_and_recommendations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $student = User::factory()->create([
            'name' => 'Amina',
            'role' => 'student',
            'total_xp' => 120,
        ]);
        User::factory()->create([
            'name' => 'Youssef',
            'role' => 'student',
            'total_xp' => 200,
        ]);

        $completedExercise = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        $completedSubmission = Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $completedExercise->id,
            'source_code' => 'console.log("Bonjour CodeQuest");',
            'status' => 'accepted',
            'passed_tests' => 1,
            'total_tests' => 1,
        ]);
        ExerciseCompletion::create([
            'user_id' => $student->id,
            'exercise_id' => $completedExercise->id,
            'submission_id' => $completedSubmission->id,
            'xp_earned' => 10,
            'completed_at' => now(),
        ]);
        XpTransaction::create([
            'user_id' => $student->id,
            'submission_id' => $completedSubmission->id,
            'amount' => 10,
            'reason' => 'exercise_completed',
        ]);

        $inProgressExercise = Exercise::where('slug', 'verifier-nombre-pair')->firstOrFail();
        Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $inProgressExercise->id,
            'source_code' => 'function solution(nombre) { return nombre % 2 === 0; }',
            'status' => 'rejected',
            'passed_tests' => 1,
            'total_tests' => 2,
        ]);

        $badge = Badge::where('code', 'first_completion')->firstOrFail();
        $student->badges()->attach($badge->id, ['awarded_at' => now()]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aller au contenu principal')
            ->assertSee('Navigation principale')
            ->assertSee('role="progressbar"', false)
            ->assertSee('Quête de la semaine')
            ->assertSee('1 / 3')
            ->assertSee('120')
            ->assertSee('Apprenti')
            ->assertSee('Exercices réussis')
            ->assertSee('1')
            ->assertSee('Exercices en cours')
            ->assertSee('Vérifier si un nombre est pair')
            ->assertSee('Historique des soumissions')
            ->assertSee('À corriger')
            ->assertSee('Premier défi')
            ->assertSee('Classement général')
            ->assertSee('#2')
            ->assertSee('Recommandations')
            ->assertSee('Apprentissage')
            ->assertDontSee('Parcours d’apprentissage')
            ->assertDontSee('Progression du cours')
            ->assertSee('Calculer la somme d’un tableau');
    }
}
