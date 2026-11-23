<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentLearningPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_browse_and_search_published_learning_paths(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $path = LearningPath::where('slug', 'bases-javascript')->firstOrFail();
        $questionCount = $path->chapters()
            ->withCount('quizQuestions')
            ->get()
            ->sum('quiz_questions_count');

        $this->actingAs($student)
            ->get(route('student.learning'))
            ->assertOk()
            ->assertSee('Apprentissage')
            ->assertSee($path->title)
            ->assertSee('Variables et types')
            ->assertSee('0 / '.$questionCount.' QCM réussis')
            ->assertSee('Verrouillé')
            ->assertDontSee('correct_option');

        Livewire::actingAs($student)
            ->test('student.learning')
            ->set('search', 'boucles')
            ->assertSee($path->title)
            ->set('search', 'cours inexistant')
            ->assertSee('Aucun cours trouvé')
            ->assertDontSee($path->title);
    }

    public function test_learning_page_requires_authentication(): void
    {
        $this->get(route('student.learning'))
            ->assertRedirect(route('login'));
    }
}
