<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Exercise;
use App\Models\LearningStreak;
use App\Models\User;
use App\Jobs\ProcessExerciseSubmission;
use App\Services\GamificationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProgressionGamificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_difficulty_levels_lock_exercises_and_server_route(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'role' => 'student',
            'total_xp' => 0,
        ]);
        $mediumExercise = \App\Models\Exercise::where('slug', 'somme-d-un-tableau')->firstOrFail();

        $this->assertFalse($mediumExercise->isUnlockedFor($user));

        Livewire::actingAs($user)
            ->test('student.exercises')
            ->assertSee('Verrouillé — niveau 2');

        $this->actingAs($user)
            ->get(route('student.exercise-show', $mediumExercise))
            ->assertForbidden();

        $user->update(['total_xp' => 100]);

        $this->assertTrue($mediumExercise->isUnlockedFor($user->fresh()));

        $this->actingAs($user)
            ->get(route('student.exercise-show', $mediumExercise))
            ->assertOk();
    }

    public function test_learning_streak_counts_distinct_consecutive_days_and_awards_badge(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['role' => 'student']);
        $gamification = app(GamificationService::class);

        $gamification->recordLearningActivity(
            $user,
            CarbonImmutable::parse('2026-11-10 09:00:00')
        );
        $gamification->recordLearningActivity(
            $user,
            CarbonImmutable::parse('2026-11-10 18:00:00')
        );

        $this->assertSame(1, LearningStreak::where('user_id', $user->id)->value('current_streak'));

        $gamification->recordLearningActivity(
            $user,
            CarbonImmutable::parse('2026-11-11 09:00:00')
        );
        $gamification->recordLearningActivity(
            $user,
            CarbonImmutable::parse('2026-11-12 09:00:00')
        );

        $streak = LearningStreak::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(3, $streak->current_streak);
        $this->assertSame(3, $streak->longest_streak);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => Badge::where('code', 'three_day_streak')->value('id'),
        ]);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Série d’apprentissage')
            ->assertSee('Badges et récompenses')
            ->assertSee('Sur ta lancée');

        $gamification->recordLearningActivity(
            $user,
            CarbonImmutable::parse('2026-11-14 09:00:00')
        );

        $streak->refresh();
        $this->assertSame(1, $streak->current_streak);
        $this->assertSame(3, $streak->longest_streak);
    }

    public function test_submitting_an_exercise_records_activity_and_queues_correction(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['role' => 'student']);
        $exercise = Exercise::where('slug', 'afficher-un-message')->firstOrFail();
        Queue::fake();

        Livewire::actingAs($user)
            ->test('student.exercise-show', ['exercise' => $exercise])
            ->set('sourceCode', 'console.log("Bonjour CodeQuest");')
            ->call('submitExercise')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('learning_streaks', [
            'user_id' => $user->id,
            'current_streak' => 1,
            'longest_streak' => 1,
        ]);
        $this->assertDatabaseHas('submissions', [
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'status' => 'pending',
        ]);
        Queue::assertPushed(ProcessExerciseSubmission::class);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Historique des activités')
            ->assertSee('Afficher un message')
            ->assertSee('En cours');
    }
}
