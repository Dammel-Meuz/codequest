<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChapterQuizQuestion;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Submission;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\ChapterQuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_weekly_and_category_rankings_use_their_expected_xp_totals(): void
    {
        $alice = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'role' => 'student',
            'total_xp' => 200,
        ]);
        $bob = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'role' => 'student',
            'total_xp' => 120,
        ]);
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'total_xp' => 10000,
        ]);

        $categoryA = Category::create([
            'name' => 'Fonctions',
            'slug' => 'fonctions',
            'is_active' => true,
        ]);
        $categoryB = Category::create([
            'name' => 'Tableaux',
            'slug' => 'tableaux',
            'is_active' => true,
        ]);

        $exerciseA = $this->createExercise($categoryA, 'Fonction A', 'fonction-a');
        $exerciseB = $this->createExercise($categoryB, 'Tableau B', 'tableau-b');
        $this->awardXp($alice, $exerciseA, 20, now()->subDay());
        $this->awardXp($bob, $exerciseA, 30, now()->subDays(2));
        $this->awardXp($alice, $exerciseB, 100, now()->subDays(2));
        $this->awardXp($bob, $exerciseA, 90, now()->subWeeks(2));

        Livewire::actingAs($bob)
            ->test('student.leaderboard')
            ->assertSeeInOrder(['Alice', 'Bob'])
            ->assertSee('200 XP')
            ->assertDontSee('Admin');
        Livewire::actingAs($bob)
            ->test('student.leaderboard')
            ->assertDontSee('alice@example.com')
            ->assertDontSee('bob@example.com')
            ->assertDontSee('admin@example.com');

        Livewire::actingAs($alice)
            ->test('student.leaderboard')
            ->set('period', 'weekly')
            ->set('categoryId', (string) $categoryA->id)
            ->assertSeeInOrder(['Bob', 'Alice'])
            ->assertSee('30 XP')
            ->assertSee('20 XP')
            ->assertSee('Fonctions');

        Livewire::actingAs($bob)
            ->test('student.leaderboard')
            ->set('categoryId', (string) $categoryB->id)
            ->assertSee('Alice')
            ->assertSee('100 XP')
            ->assertDontSee('Bob');
    }

    public function test_public_student_profile_does_not_expose_email(): void
    {
        $student = User::factory()->create([
            'name' => 'Amina Diallo',
            'email' => 'amina@example.com',
            'role' => 'student',
            'total_xp' => 50,
        ]);

        $viewer = User::factory()->create(['role' => 'student']);

        $this->actingAs($viewer)
            ->get(route('student.public-profile', $student))
            ->assertOk()
            ->assertSee('Amina Diallo')
            ->assertSee('50')
            ->assertDontSee('amina@example.com');
    }

    public function test_weekly_rankings_include_quiz_xp_but_category_rankings_only_include_exercise_xp(): void
    {
        $alice = User::factory()->create(['name' => 'Alice', 'role' => 'student']);
        $bob = User::factory()->create(['name' => 'Bob', 'role' => 'student']);
        $category = Category::create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);
        $exercise = $this->createExercise($category, 'Défi JavaScript', 'defi-javascript');
        $this->awardXp($alice, $exercise, 5, now());

        $path = LearningPath::create([
            'title' => 'Cours JavaScript',
            'slug' => 'cours-javascript',
            'is_published' => true,
        ]);
        $chapter = $path->chapters()->create([
            'title' => 'Variables',
            'slug' => 'variables',
        ]);
        $question = ChapterQuizQuestion::create([
            'learning_path_chapter_id' => $chapter->id,
            'prompt' => 'Quel mot-clé déclare une variable réassignable ?',
            'options' => ['a' => 'let', 'b' => 'const', 'c' => 'fixed', 'd' => 'final'],
            'correct_option' => 'a',
            'xp_reward' => 20,
            'sort_order' => 0,
        ]);
        app(ChapterQuizService::class)->answer($bob, $question, 'a');

        Livewire::actingAs($alice)
            ->test('student.leaderboard')
            ->set('period', 'weekly')
            ->assertSeeInOrder(['Bob', 'Alice'])
            ->assertSee('20 XP')
            ->assertSee('5 XP');

        Livewire::actingAs($alice)
            ->test('student.leaderboard')
            ->set('period', 'weekly')
            ->set('categoryId', (string) $category->id)
            ->assertSee('Alice')
            ->assertSee('5 XP')
            ->assertDontSee('Bob');
    }

    private function createExercise(Category $category, string $title, string $slug): Exercise
    {
        return Exercise::create([
            'category_id' => $category->id,
            'title' => $title,
            'slug' => $slug,
            'description' => $title,
            'difficulty' => 'easy',
            'xp_reward' => 10,
            'language' => 'javascript',
            'is_published' => true,
        ]);
    }

    private function awardXp(User $user, Exercise $exercise, int $amount, $createdAt): void
    {
        $submission = Submission::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'source_code' => 'function solution() {}',
            'status' => 'accepted',
        ]);

        $transaction = new XpTransaction([
            'user_id' => $user->id,
            'submission_id' => $submission->id,
            'amount' => $amount,
            'reason' => 'exercise_completed',
        ]);
        $transaction->created_at = $createdAt;
        $transaction->updated_at = $createdAt;
        $transaction->save();
    }
}
