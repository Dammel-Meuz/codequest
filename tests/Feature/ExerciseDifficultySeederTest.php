<?php

namespace Tests\Feature;

use App\Models\Exercise;
use Database\Seeders\ExerciseDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseDifficultySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_hundred_exercises_per_difficulty_idempotently(): void
    {
        $this->seed(ExerciseDifficultySeeder::class);
        $this->seed(ExerciseDifficultySeeder::class);

        foreach (['easy', 'medium', 'hard', 'expert'] as $difficulty) {
            $this->assertSame(
                100,
                Exercise::query()->where('difficulty', $difficulty)->count(),
                "Unexpected exercise count for {$difficulty}."
            );
        }

        $generatedExercises = Exercise::query()
            ->where('slug', 'like', 'serie-%')
            ->withCount([
                'testCases',
                'testCases as hidden_test_cases_count' => fn ($query) => $query->where('is_hidden', true),
            ])
            ->get();

        $this->assertNotEmpty($generatedExercises);
        $this->assertTrue($generatedExercises->every(
            fn (Exercise $exercise) => $exercise->test_cases_count === 3
                && $exercise->hidden_test_cases_count === 1
        ));
    }
}
