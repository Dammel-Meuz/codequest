<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseCompletion;
use App\Models\LearningPath;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_chapters_and_assign_exercise_prerequisites_without_cycles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin.learning-paths')
            ->set('title', 'Parcours JavaScript')
            ->set('description', 'Apprendre les bases')
            ->set('is_published', true)
            ->set('chapters', [
                [
                    'title' => 'Premiers pas',
                    'description' => 'Découvrir JavaScript',
                    'sort_order' => 0,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $path = LearningPath::where('slug', 'parcours-javascript')->firstOrFail();
        $chapter = $path->chapters()->firstOrFail();
        $this->assertSame('Premiers pas', $chapter->title);

        Livewire::actingAs($admin)
            ->test('admin.learning-paths')
            ->call('edit', $path->id)
            ->assertSet('chapters.0.title', 'Premiers pas')
            ->set('title', 'Parcours JavaScript avancé')
            ->set('chapters.0.title', 'Découverte')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Parcours JavaScript avancé', $path->fresh()->title);
        $this->assertSame('Découverte', $chapter->fresh()->title);

        $category = Category::create([
            'name' => 'Bases',
            'slug' => 'bases',
            'is_active' => true,
        ]);
        $firstExercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Premier défi',
            'slug' => 'premier-defi',
            'description' => 'Premier exercice',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'is_published' => true,
        ]);
        $nextExercise = Exercise::create([
            'category_id' => $category->id,
            'title' => 'Défi suivant',
            'slug' => 'defi-suivant',
            'description' => 'Exercice suivant',
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'is_published' => true,
        ]);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->call('edit', $nextExercise->id)
            ->set('chapter_id', (string) $chapter->id)
            ->set('prerequisiteIds', [(string) $firstExercise->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($chapter->id, $nextExercise->fresh()->learning_path_chapter_id);
        $this->assertTrue($nextExercise->fresh()->prerequisites->contains('id', $firstExercise->id));

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->call('edit', $firstExercise->id)
            ->set('prerequisiteIds', [(string) $nextExercise->id])
            ->call('save')
            ->assertHasErrors(['prerequisiteIds']);

        $this->assertCount(0, $firstExercise->fresh()->prerequisites);
    }

    public function test_exercise_stays_locked_until_its_prerequisite_is_completed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $student = User::factory()->create(['role' => 'student']);
        $prerequisite = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        $lockedExercise = Exercise::where('slug', 'affichage-sur-le-console')->firstOrFail();

        $this->assertFalse($lockedExercise->isUnlockedFor($student));

        $this->actingAs($student)
            ->get(route('student.exercise-show', $lockedExercise))
            ->assertForbidden();

        Livewire::actingAs($student)
            ->test('student.exercises')
            ->assertSee('Verrouillé — prérequis');

        $submission = Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $prerequisite->id,
            'source_code' => 'console.log("Bonjour CodeQuest");',
            'status' => 'accepted',
        ]);
        ExerciseCompletion::create([
            'user_id' => $student->id,
            'exercise_id' => $prerequisite->id,
            'submission_id' => $submission->id,
            'xp_earned' => $prerequisite->xp_reward,
            'completed_at' => now(),
        ]);

        $this->assertTrue($lockedExercise->fresh()->isUnlockedFor($student));

        $this->actingAs($student)
            ->get(route('student.exercise-show', $lockedExercise))
            ->assertOk();
    }
}
