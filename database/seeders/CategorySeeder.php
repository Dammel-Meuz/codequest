<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Variables et constantes',
                'slug' => 'variables-constantes',
                'description' => 'Apprendre à déclarer et utiliser les variables et constantes en JavaScript.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Types de données',
                'slug' => 'types-de-donnees',
                'description' => 'Découvrir les différents types de données en JavaScript.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Opérateurs',
                'slug' => 'operateurs',
                'description' => 'Maîtriser les opérateurs arithmétiques, logiques et de comparaison.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Conditions',
                'slug' => 'conditions',
                'description' => 'Utiliser les conditions pour contrôler le déroulement du programme.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Boucles',
                'slug' => 'boucles',
                'description' => 'Répéter des instructions avec les différentes structures de boucles.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Fonctions',
                'slug' => 'fonctions',
                'description' => 'Créer et utiliser des fonctions pour organiser le code.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Tableaux',
                'slug' => 'tableaux',
                'description' => 'Créer, parcourir et manipuler des tableaux en JavaScript.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Objets',
                'slug' => 'objets',
                'description' => 'Comprendre les objets et manipuler leurs propriétés et méthodes.',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
