<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\StudentExerciseDraft;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class StudentExerciseDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_is_restored_per_student_and_per_exercise(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $anotherStudent = User::factory()->create(['role' => 'student']);
        $exercise = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        $otherExercise = Exercise::where('slug', 'compter-les-caracteres')->firstOrFail();
        $draftCode = 'console.log("Mon brouillon");';

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertSet('sourceCode', $exercise->starter_code)
            ->call('saveDraft', $draftCode)
            ->assertHasNoErrors()
            ->assertSet('draftSaveStatus', 'Brouillon enregistré');

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertSet('sourceCode', $draftCode)
            ->assertSet('draftSaveStatus', 'Brouillon restauré');

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $otherExercise])
            ->assertSet('sourceCode', $otherExercise->starter_code);

        Livewire::actingAs($anotherStudent)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->assertSet('sourceCode', $exercise->starter_code);

        $this->assertDatabaseCount('student_exercise_drafts', 1);
        $this->assertDatabaseHas('student_exercise_drafts', [
            'user_id' => $student->id,
            'exercise_id' => $exercise->id,
            'source_code' => $draftCode,
        ]);
    }

    public function test_submission_keeps_its_code_snapshot_separate_from_the_editable_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        Queue::fake();

        $student = User::factory()->create(['role' => 'student']);
        $exercise = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        $submittedCode = 'console.log("Code envoyé");';
        $laterDraftCode = 'console.log("Brouillon modifié");';

        $component = Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->set('sourceCode', $submittedCode)
            ->call('submitExercise')
            ->assertHasNoErrors()
            ->assertSee('Dernière soumission corrigée')
            ->call('saveDraft', $laterDraftCode)
            ->assertHasNoErrors();

        $submission = Submission::where('user_id', $student->id)
            ->where('exercise_id', $exercise->id)
            ->latest('id')
            ->firstOrFail();
        $draft = StudentExerciseDraft::where('user_id', $student->id)
            ->where('exercise_id', $exercise->id)
            ->firstOrFail();

        $this->assertSame($submittedCode, $submission->source_code);
        $this->assertSame($laterDraftCode, $draft->source_code);

        $component->call('restoreSubmittedCode')
            ->assertHasNoErrors()
            ->assertSet('sourceCode', $submittedCode);

        $this->assertSame(
            $submittedCode,
            StudentExerciseDraft::where('user_id', $student->id)
                ->where('exercise_id', $exercise->id)
                ->value('source_code')
        );
    }
}
