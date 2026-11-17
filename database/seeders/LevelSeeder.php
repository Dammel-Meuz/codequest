<?php

namespace Database\Seeders;


use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'level_number' => 1,
                'name' => 'Débutant',
                'min_xp' => 0,
                'description' => 'Tu fais tes premiers pas dans la programmation.',
            ],
            [
                'level_number' => 2,
                'name' => 'Apprenti',
                'min_xp' => 100,
                'description' => 'Tu commences à maîtriser les bases du code.',
            ],
            [
                'level_number' => 3,
                'name' => 'Explorateur',
                'min_xp' => 250,
                'description' => 'Tu explores de nouvelles notions de programmation.',
            ],
            [
                'level_number' => 4,
                'name' => 'Codeur',
                'min_xp' => 500,
                'description' => 'Tu développes tes compétences en programmation.',
            ],
            [
                'level_number' => 5,
                'name' => 'Développeur',
                'min_xp' => 850,
                'description' => 'Tu sais résoudre des problèmes plus complexes.',
            ],
            [
                'level_number' => 6,
                'name' => 'Expert',
                'min_xp' => 1300,
                'description' => 'Tu maîtrises de nombreuses notions avancées.',
            ],
            [
                'level_number' => 7,
                'name' => 'Maître',
                'min_xp' => 1900,
                'description' => 'Tu démontres une excellente maîtrise du code.',
            ],
            [
                'level_number' => 8,
                'name' => 'Élite',
                'min_xp' => 2700,
                'description' => 'Tu fais partie des programmeurs les plus expérimentés.',
            ],
            [
                'level_number' => 9,
                'name' => 'Légende',
                'min_xp' => 3700,
                'description' => 'Tes compétences témoignent d\'une grande expérience.',
            ],
            [
                'level_number' => 10,
                'name' => 'Grand Maître',
                'min_xp' => 5000,
                'description' => 'Tu as atteint le sommet de CodeQuest.',
            ],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(
                ['level_number' => $level['level_number']],
                $level
            );
        }
    }
}
