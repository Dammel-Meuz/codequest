<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            [
                'category' => 'variables-constantes',
                'title' => 'Afficher un message',
                'slug' => 'afficher-un-message',
                'description' => 'Affiche le message Bonjour CodeQuest dans la console avec console.log().',
                'hints' => [
                    'La fonction console.log affiche une valeur dans la console.',
                    'Le texte à afficher doit être écrit comme une chaîne de caractères.',
                ],
                'success_explanation' => 'console.log(...) affiche l’expression passée en argument. Une chaîne de caractères permet ici de représenter le message.',
                'difficulty' => 'easy',
                'xp_reward' => 10,
                'starter_code' => '// Affiche "Bonjour CodeQuest" dans la console',
                'sort_order' => 1,
            ],
            [
                'category' => 'variables-constantes',
                'title' => 'Afficher plusieurs lignes',
                'slug' => 'affichage-sur-le-console',
                'description' => 'Affiche Bonjour puis CodeQuest sur deux lignes séparées dans la console.',
                'hints' => [
                    'Chaque appel à console.log affiche une ligne.',
                    'Tu peux appeler console.log plusieurs fois, dans l’ordre demandé.',
                ],
                'success_explanation' => 'Chaque appel à console.log produit une ligne. Deux appels successifs permettent donc de respecter l’ordre demandé.',
                'difficulty' => 'easy',
                'xp_reward' => 10,
                'starter_code' => '// Affiche "Bonjour" puis "CodeQuest"',
                'sort_order' => 2,
            ],
            [
                'category' => 'operateurs',
                'title' => 'Additionner deux nombres',
                'slug' => 'additionner-deux-nombres',
                'description' => 'Complète la fonction solution pour retourner la somme de deux nombres.',
                'hints' => [
                    'Les deux valeurs à additionner sont déjà disponibles dans les paramètres a et b.',
                    'Utilise l’opérateur JavaScript qui additionne deux nombres.',
                ],
                'success_explanation' => 'La fonction reçoit deux nombres en paramètres. L’opérateur + calcule leur somme, que la fonction doit retourner.',
                'difficulty' => 'easy',
                'xp_reward' => 15,
                'starter_code' => "function solution(a, b) {\n    // Retourne la somme de a et b\n}",
                'sort_order' => 1,
            ],
            [
                'category' => 'conditions',
                'title' => 'Vérifier si un nombre est pair',
                'slug' => 'verifier-nombre-pair',
                'description' => 'Retourne true si le nombre reçu est pair, et false sinon.',
                'hints' => [
                    'L’opérateur % donne le reste d’une division.',
                    'Un nombre pair divisé par 2 a un reste égal à zéro.',
                ],
                'success_explanation' => 'La parité se vérifie avec le reste de la division par 2 : un reste nul indique un nombre pair.',
                'difficulty' => 'easy',
                'xp_reward' => 15,
                'starter_code' => "function solution(nombre) {\n    // Retourne true si le nombre est pair\n}",
                'sort_order' => 1,
            ],
            [
                'category' => 'operateurs',
                'title' => 'Multiplier un nombre par deux',
                'slug' => 'multiplier-par-deux',
                'description' => 'Retourne le double du nombre reçu.',
                'hints' => [
                    'Le double d’un nombre correspond à deux fois sa valeur.',
                    'Tu peux utiliser une multiplication ou additionner le nombre à lui-même.',
                ],
                'success_explanation' => 'Multiplier un nombre par 2 revient à le doubler. La fonction retourne le résultat de ce calcul.',
                'difficulty' => 'easy',
                'xp_reward' => 20,
                'starter_code' => "function solution(nombre) {\n    // Retourne le double du nombre\n}",
                'sort_order' => 2,
            ],
            [
                'category' => 'fonctions',
                'title' => 'Saluer une personne',
                'slug' => 'saluer-une-personne',
                'description' => 'Retourne le texte Bonjour, suivi du prénom reçu et d’un point d’exclamation.',
                'hints' => [
                    'Le prénom reçu se trouve dans le paramètre de la fonction.',
                    'Une chaîne modèle JavaScript peut assembler du texte et une variable.',
                ],
                'success_explanation' => 'La fonction construit une chaîne contenant le texte demandé et la valeur du prénom, puis retourne cette chaîne.',
                'difficulty' => 'easy',
                'xp_reward' => 20,
                'starter_code' => "function solution(prenom) {\n    // Construis et retourne le message de bienvenue\n}",
                'sort_order' => 2,
            ],
            [
                'category' => 'types-de-donnees',
                'title' => 'Compter les caractères',
                'slug' => 'compter-les-caracteres',
                'description' => 'Retourne le nombre de caractères de la chaîne reçue.',
                'hints' => [
                    'Les chaînes JavaScript possèdent une propriété qui indique leur longueur.',
                    'Cette propriété s’appelle length.',
                ],
                'success_explanation' => 'La propriété length d’une chaîne retourne son nombre de caractères.',
                'difficulty' => 'easy',
                'xp_reward' => 20,
                'starter_code' => "function solution(texte) {\n    // Retourne la longueur du texte\n}",
                'sort_order' => 1,
            ],
            [
                'category' => 'tableaux',
                'title' => 'Calculer la somme d’un tableau',
                'slug' => 'somme-d-un-tableau',
                'description' => 'Retourne la somme de tous les nombres du tableau reçu.',
                'hints' => [
                    'Commence par une somme initialisée à zéro.',
                    'Parcours les éléments du tableau et ajoute chacun à la somme.',
                ],
                'success_explanation' => 'La somme d’un tableau se calcule en accumulant chacun de ses éléments, en partant de zéro.',
                'difficulty' => 'medium',
                'xp_reward' => 20,
                'starter_code' => "function solution(nombres) {\n    // Retourne la somme des nombres\n}",
                'sort_order' => 1,
            ],
            [
                'category' => 'fonctions',
                'title' => 'Inverser une chaîne',
                'slug' => 'inverser-une-chaine',
                'description' => 'Retourne la chaîne de caractères reçue dans l’ordre inverse.',
                'hints' => [
                    'Tu peux d’abord transformer la chaîne en tableau de caractères.',
                    'Les méthodes reverse et join peuvent aider à inverser puis reconstruire la chaîne.',
                ],
                'success_explanation' => 'La solution inverse l’ordre des caractères puis les rassemble à nouveau pour produire la chaîne retournée.',
                'difficulty' => 'medium',
                'xp_reward' => 20,
                'starter_code' => "function solution(texte) {\n    // Retourne le texte inversé\n}",
                'sort_order' => 1,
            ],
        ];

        foreach ($exercises as $exercise) {
            $category = Category::where('slug', $exercise['category'])->firstOrFail();
            unset($exercise['category']);

            Exercise::updateOrCreate(
                ['slug' => $exercise['slug']],
                array_merge($exercise, [
                    'category_id' => $category->id,
                    'language' => 'javascript',
                    'is_published' => true,
                ])
            );
        }
    }
}
