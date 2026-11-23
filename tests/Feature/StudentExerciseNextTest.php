<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentExerciseNextTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_submission_shows_the_next_unlocked_exercise_in_the_learning_path(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        [$first, $next] = $this->createChapterExercises();

        Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $first->id,
            'source_code' => 'function solution() { return true; }',
            'status' => 'accepted',
            'passed_tests' => 1,
            'total_tests' => 1,
        ]);

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $first])
            ->assertSee('Exercice suivant : '.$next->title)
            ->assertSee(route('student.exercise-show', $next));
    }

    public function test_failed_submission_does_not_show_the_next_exercise_button(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        [$first] = $this->createChapterExercises();

        Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $first->id,
            'source_code' => 'function solution() { return false; }',
            'status' => 'rejected',
            'passed_tests' => 0,
            'total_tests' => 1,
        ]);

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $first])
            ->assertDontSee('Exercice suivant :');
    }

    public function test_next_exercise_button_is_hidden_when_the_next_exercise_is_locked(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create([
            'name' => 'Bases',
            'slug' => 'bases',
            'is_active' => true,
        ]);
        $first = $this->createExercise($category, 'Premier exercice', 'premier-exercice', 'easy', 1);
        $locked = $this->createExercise($category, 'Exercice verrouillé', 'exercice-verrouille', 'medium', 2);

        Submission::create([
            'user_id' => $student->id,
            'exercise_id' => $first->id,
            'source_code' => 'function solution() { return true; }',
            'status' => 'accepted',
            'passed_tests' => 1,
            'total_tests' => 1,
        ]);

        Livewire::actingAs($student)
            ->test('student.exercise-show', ['exercise' => $first])
            ->assertDontSee('Exercice suivant : '.$locked->title);
    }

    private function createChapterExercises(): array
    {
        $category = Category::create([
            'name' => 'Bases',
            'slug' => 'bases',
            'is_active' => true,
        ]);
        $path = LearningPath::create([
            'title' => 'Parcours JavaScript',
            'slug' => 'parcours-javascript',
            'is_published' => true,
        ]);
        $chapter = $path->chapters()->create([
            'title' => 'Premiers pas',
            'slug' => 'premiers-pas',
            'sort_order' => 1,
        ]);
        $nextChapter = $path->chapters()->create([
            'title' => 'Variables',
            'slug' => 'variables',
            'sort_order' => 2,
        ]);

        return [
            $this->createExercise($category, 'Premier exercice', 'premier-exercice', 'easy', 1, $chapter->id),
            $this->createExercise($category, 'Exercice suivant', 'exercice-suivant', 'easy', 1, $nextChapter->id),
        ];
    }

    private function createExercise(
        Category $category,
        string $title,
        string $slug,
        string $difficulty,
        int $sortOrder,
        ?int $chapterId = null,
    ): Exercise {
        return Exercise::create([
            'category_id' => $category->id,
            'learning_path_chapter_id' => $chapterId,
            'title' => $title,
            'slug' => $slug,
            'description' => $title,
            'difficulty' => $difficulty,
            'xp_reward' => 10,
            'language' => 'javascript',
            'is_published' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
