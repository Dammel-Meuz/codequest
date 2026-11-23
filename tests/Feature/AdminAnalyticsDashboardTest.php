<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseCompletion;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_reports_exercise_outcomes_student_progress_and_recent_activity(): void
    {
        $category = Category::create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);
        $difficultExercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Défi à examiner',
            'slug' => 'defi-a-examiner',
            'description' => 'Exercice avec un taux faible',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'is_published' => true,
        ]);
        $masteredExercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Défi maîtrisé',
            'slug' => 'defi-maitrise',
            'description' => 'Exercice réussi',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'is_published' => true,
        ]);

        $firstStudent = User::factory()->create([
            'name' => 'Amina Progression',
            'role' => 'student',
            'total_xp' => 10,
        ]);
        $secondStudent = User::factory()->create([
            'name' => 'Youssef Activité',
            'role' => 'student',
            'total_xp' => 20,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $privateSourceCode = 'PRIVATE_SOURCE_CODE_DO_NOT_RENDER';

        $firstSuccess = Submission::create([
            'user_id' => $firstStudent->id,
            'exercise_id' => $difficultExercise->id,
            'source_code' => $privateSourceCode,
            'status' => 'accepted',
        ]);

        foreach ([
            [$firstStudent, 'rejected'],
            [$firstStudent, 'rejected'],
            [$secondStudent, 'rejected'],
            [$secondStudent, 'rejected'],
        ] as [$student, $status]) {
            Submission::create([
                'user_id' => $student->id,
                'exercise_id' => $difficultExercise->id,
                'source_code' => $privateSourceCode,
                'status' => $status,
            ]);
        }

        Submission::create([
            'user_id' => $secondStudent->id,
            'exercise_id' => $difficultExercise->id,
            'source_code' => $privateSourceCode,
            'status' => 'pending',
        ]);

        ExerciseCompletion::create([
            'user_id' => $firstStudent->id,
            'exercise_id' => $difficultExercise->id,
            'submission_id' => $firstSuccess->id,
            'xp_earned' => 10,
            'completed_at' => now(),
        ]);

        $secondSuccess = Submission::create([
            'user_id' => $secondStudent->id,
            'exercise_id' => $masteredExercise->id,
            'source_code' => $privateSourceCode,
            'status' => 'accepted',
        ]);
        ExerciseCompletion::create([
            'user_id' => $secondStudent->id,
            'exercise_id' => $masteredExercise->id,
            'submission_id' => $secondSuccess->id,
            'xp_earned' => 10,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test('admin.dashboard')
            ->assertSee('Taux global de réussite')
            ->assertSee('33.3 %')
            ->assertSee('Résultats par exercice')
            ->assertSee('Défi à examiner')
            ->assertSee('20.0 %')
            ->assertSee('À examiner')
            ->assertSee('Sans réussite')
            ->assertSee('Progression des étudiants')
            ->assertSee('Amina Progression')
            ->assertSee('Youssef Activité')
            ->assertSee('Activité récente')
            ->assertDontSee($privateSourceCode);
    }

    public function test_admin_dashboard_shows_empty_states_when_no_activity_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin.dashboard')
            ->assertSee('Aucun exercice publié à analyser.')
            ->assertSee('Aucun étudiant inscrit.')
            ->assertSee('Aucune soumission enregistrée.')
            ->assertSee('Taux global de réussite')
            ->assertSee('—');
    }

    public function test_admin_dashboard_is_forbidden_to_students(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
