<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_real_mission_student_and_leaderboard_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        User::factory()->create([
            'name' => 'First ranked student',
            'role' => 'student',
            'total_xp' => 245,
        ]);
        User::factory()->create([
            'name' => 'Second ranked student',
            'role' => 'student',
            'total_xp' => 100,
        ]);
        User::factory()->create([
            'name' => 'Administrator',
            'role' => 'admin',
            'total_xp' => 5000,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Le code devient')
            ->assertSee('Choisis ta prochaine mission')
            ->assertSee('Afficher un message')
            ->assertSee('missions publiées')
            ->assertSee('langages proposés')
            ->assertSee('étudiants inscrits')
            ->assertSee('100 XP avant Apprenti')
            ->assertSee('Comment ça marche ?')
            ->assertSee('Codeur·se 01')
            ->assertSee('245 XP')
            ->assertDontSee('First ranked student')
            ->assertDontSee('Administrator');
    }

    public function test_homepage_shows_empty_state_when_no_missions_or_students_exist(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Les missions arrivent bientôt.')
            ->assertSee('Le classement se construit ici.')
            ->assertSee('Aucun exercice n’est actuellement publié.');
    }
}
