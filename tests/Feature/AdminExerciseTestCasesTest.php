<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminExerciseTestCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_remove_exercise_test_cases(): void
    {
        $category = Category::create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->set('title', 'Addition')
            ->set('description', 'Additionner deux nombres')
            ->set('hints', [
                'Repère les deux valeurs reçues par la fonction.',
                'Additionne ces valeurs puis retourne le résultat.',
            ])
            ->set('success_explanation', 'Une fonction retourne le résultat de son calcul.')
            ->set('category_id', $category->id)
            ->set('testCases', [
                [
                    'input' => '{"function":"solution","args":[2,3]}',
                    'expected' => '5',
                    'hidden' => false,
                ],
                [
                    'input' => '{"function":"solution","args":[-1,4]}',
                    'expected' => '3',
                    'hidden' => true,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $exercise = Exercise::where('slug', 'addition')->firstOrFail();
        $this->assertSame([
            'Repère les deux valeurs reçues par la fonction.',
            'Additionne ces valeurs puis retourne le résultat.',
        ], $exercise->hints);
        $this->assertSame('Une fonction retourne le résultat de son calcul.', $exercise->success_explanation);

        $this->assertDatabaseCount('exercise_test_cases', 2);
        $this->assertDatabaseHas('exercise_test_cases', [
            'exercise_id' => $exercise->id,
            'input_data' => '{"function":"solution","args":[-1,4]}',
            'expected_output' => '3',
            'is_hidden' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->call('edit', $exercise->id)
            ->assertSet('testCases.0.input', '{"function":"solution","args":[2,3]}')
            ->assertSet('hints.0', 'Repère les deux valeurs reçues par la fonction.')
            ->assertSet('success_explanation', 'Une fonction retourne le résultat de son calcul.')
            ->set('hints.0', 'Observe les paramètres.')
            ->set('success_explanation', 'Retourne la somme calculée.')
            ->set('testCases.0.expected', '6')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Observe les paramètres.', $exercise->fresh()->hints[0]);
        $this->assertSame('Retourne la somme calculée.', $exercise->fresh()->success_explanation);

        $this->assertDatabaseHas('exercise_test_cases', [
            'exercise_id' => $exercise->id,
            'expected_output' => '6',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->call('edit', $exercise->id)
            ->call('removeTestCase', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('exercise_test_cases', 1);
        $this->assertDatabaseHas('exercise_test_cases', [
            'exercise_id' => $exercise->id,
            'expected_output' => '3',
        ]);
    }

    public function test_invalid_json_does_not_save_exercise(): void
    {
        $category = Category::create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->set('title', 'Invalid test')
            ->set('description', 'Test invalide')
            ->set('category_id', $category->id)
            ->set('testCases', [
                [
                    'input' => '{invalid json}',
                    'expected' => 'null',
                    'hidden' => false,
                ],
            ])
            ->call('save')
            ->assertHasErrors(['testCases.0.input']);

        $this->assertDatabaseMissing('exercises', [
            'slug' => 'invalid-test',
        ]);
    }

    public function test_admin_cannot_configure_more_than_five_hints(): void
    {
        $category = Category::create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin.exercises')
            ->set('title', 'Trop d’indices')
            ->set('description', 'Tester la limite des indices')
            ->set('category_id', $category->id)
            ->set('hints', array_fill(0, 6, 'Indice de test'))
            ->call('save')
            ->assertHasErrors(['hints']);

        $this->assertDatabaseMissing('exercises', [
            'slug' => 'trop-dindices',
        ]);
    }
}
