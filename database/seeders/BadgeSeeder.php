<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'code' => 'first_completion',
                'name' => 'Premier défi',
                'description' => 'Réussis ton premier exercice.',
                'icon' => 'medal',
                'criteria_type' => 'completions',
                'criteria_value' => 1,
            ],
            [
                'code' => 'five_completions',
                'name' => 'Explorateur',
                'description' => 'Réussis 5 exercices.',
                'icon' => 'compass',
                'criteria_type' => 'completions',
                'criteria_value' => 5,
            ],
            [
                'code' => 'ten_completions',
                'name' => 'Codeur assidu',
                'description' => 'Réussis 10 exercices.',
                'icon' => 'trophy',
                'criteria_type' => 'completions',
                'criteria_value' => 10,
            ],
            [
                'code' => 'hundred_xp',
                'name' => 'Chasseur d’XP',
                'description' => 'Atteins 100 XP.',
                'icon' => 'spark',
                'criteria_type' => 'xp',
                'criteria_value' => 100,
            ],
            [
                'code' => 'five_hundred_xp',
                'name' => 'Maître de l’XP',
                'description' => 'Atteins 500 XP.',
                'icon' => 'crown',
                'criteria_type' => 'xp',
                'criteria_value' => 500,
            ],
            [
                'code' => 'three_day_streak',
                'name' => 'Sur ta lancée',
                'description' => 'Apprends 3 jours de suite.',
                'icon' => 'flame',
                'criteria_type' => 'streak',
                'criteria_value' => 3,
            ],
            [
                'code' => 'seven_day_streak',
                'name' => 'Série parfaite',
                'description' => 'Apprends 7 jours de suite.',
                'icon' => 'calendar',
                'criteria_type' => 'streak',
                'criteria_value' => 7,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['code' => $badge['code']],
                $badge
            );
        }
    }
}
