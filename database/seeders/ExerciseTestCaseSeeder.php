<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ExerciseTestCase;
use Illuminate\Database\Seeder;

class ExerciseTestCaseSeeder extends Seeder
{
    public function run(): void
    {
        $testCases = [
            'afficher-un-message' => [
                [
                    'input_data' => [],
                    'expected_output' => 'Bonjour CodeQuest',
                    'is_hidden' => false,
                ],
            ],
            'affichage-sur-le-console' => [
                [
                    'input_data' => [],
                    'expected_output' => ['Bonjour', 'CodeQuest'],
                    'is_hidden' => false,
                ],
            ],
            'additionner-deux-nombres' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => [2, 3]],
                    'expected_output' => 5,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [-4, 7]],
                    'expected_output' => 3,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [100, 250]],
                    'expected_output' => 350,
                    'is_hidden' => true,
                ],
            ],
            'verifier-nombre-pair' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => [8]],
                    'expected_output' => true,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [7]],
                    'expected_output' => false,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [0]],
                    'expected_output' => true,
                    'is_hidden' => true,
                ],
            ],
            'multiplier-par-deux' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => [6]],
                    'expected_output' => 12,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [-3]],
                    'expected_output' => -6,
                    'is_hidden' => true,
                ],
            ],
            'saluer-une-personne' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => ['Amina']],
                    'expected_output' => 'Bonjour, Amina !',
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => ['CodeQuest']],
                    'expected_output' => 'Bonjour, CodeQuest !',
                    'is_hidden' => true,
                ],
            ],
            'compter-les-caracteres' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => ['CodeQuest']],
                    'expected_output' => 9,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => ['']],
                    'expected_output' => 0,
                    'is_hidden' => true,
                ],
            ],
            'somme-d-un-tableau' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => [[1, 2, 3, 4]]],
                    'expected_output' => 10,
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => [[]]],
                    'expected_output' => 0,
                    'is_hidden' => true,
                ],
            ],
            'inverser-une-chaine' => [
                [
                    'input_data' => ['function' => 'solution', 'args' => ['CodeQuest']],
                    'expected_output' => 'tseuQedoC',
                    'is_hidden' => false,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => ['']],
                    'expected_output' => '',
                    'is_hidden' => true,
                ],
            ],
        ];

        foreach ($testCases as $slug => $cases) {
            $exercise = Exercise::where('slug', $slug)->firstOrFail();

            foreach ($cases as $sortOrder => $testCase) {
                ExerciseTestCase::updateOrCreate(
                    [
                        'exercise_id' => $exercise->id,
                        'sort_order' => $sortOrder,
                    ],
                    $testCase
                );
            }
        }
    }
}
