<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseTestCase;
use Illuminate\Database\Seeder;

class ExerciseDifficultySeeder extends Seeder
{
    private const EXERCISES_PER_DIFFICULTY = 100;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ExerciseSeeder::class,
            ExerciseTestCaseSeeder::class,
        ]);

        $categories = Category::query()->pluck('id', 'slug');
        $templates = $this->templates();

        foreach ($templates as $difficulty => $difficultyTemplates) {
            $remaining = max(
                0,
                self::EXERCISES_PER_DIFFICULTY
                    - Exercise::query()->where('difficulty', $difficulty)->count()
            );

            foreach ($difficultyTemplates as $template) {
                for ($variant = 1; $variant <= 20 && $remaining > 0; $variant++) {
                    $this->seedExercise($template, $difficulty, $variant, $categories);
                    $remaining--;
                }

                if ($remaining === 0) {
                    break;
                }
            }
        }
    }

    private function seedExercise(array $template, string $difficulty, int $variant, $categories): void
    {
        $slug = 'serie-'.$difficulty.'-'.$template['slug'].'-'.str_pad((string) $variant, 2, '0', STR_PAD_LEFT);
        $exercise = Exercise::updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $categories->get($template['category']),
                'title' => $template['title'].' — défi '.str_pad((string) $variant, 2, '0', STR_PAD_LEFT),
                'description' => $template['description'],
                'hints' => $template['hints'],
                'success_explanation' => $template['success_explanation'],
                'difficulty' => $difficulty,
                'xp_reward' => match ($difficulty) {
                    'easy' => 10,
                    'medium' => 20,
                    'hard' => 30,
                    'expert' => 50,
                },
                'starter_code' => $template['starter_code'],
                'language' => 'javascript',
                'is_published' => true,
                'sort_order' => 1000 + $variant,
            ]
        );

        foreach (($template['cases'])($variant) as $index => [$args, $expected]) {
            ExerciseTestCase::updateOrCreate(
                [
                    'exercise_id' => $exercise->id,
                    'sort_order' => $index + 1,
                ],
                [
                    'input_data' => ['function' => 'solution', 'args' => $args],
                    'expected_output' => $expected,
                    'is_hidden' => $index === 2,
                ]
            );
        }
    }

    private function templates(): array
    {
        return [
            'easy' => [
                $this->template(
                    'addition',
                    'operateurs',
                    'Additionner deux nombres',
                    'Écris solution(a, b) pour retourner la somme de deux nombres.',
                    'function solution(a, b) { return a + b; }',
                    ['Repère les deux paramètres.', 'Utilise l’opérateur +.'],
                    'L’opérateur + additionne les deux nombres reçus.',
                    fn (int $n) => [
                        [[$n, $n + 2], 2 * $n + 2],
                        [[-$n, $n + 5], 5],
                        [[$n * 3, $n * 4], $n * 7],
                    ],
                ),
                $this->template(
                    'pair',
                    'conditions',
                    'Tester la parité',
                    'Écris solution(nombre) pour retourner true si le nombre est pair, false sinon.',
                    'function solution(nombre) { return nombre % 2 === 0; }',
                    ['Un nombre pair est divisible par 2.', 'L’opérateur % calcule le reste.'],
                    'Le reste de la division d’un nombre pair par 2 vaut zéro.',
                    fn (int $n) => [
                        [[$n * 2], true],
                        [[$n * 2 + 1], false],
                        [[-$n * 2], true],
                    ],
                ),
                $this->template(
                    'maximum-de-deux-nombres',
                    'conditions',
                    'Trouver le plus grand de deux nombres',
                    'Écris solution(a, b) pour retourner le plus grand des deux nombres reçus.',
                    'function solution(a, b) { /* retourne le plus grand */ }',
                    ['Compare les deux paramètres.', 'Tu peux utiliser Math.max.'],
                    'La fonction retourne la plus grande des deux valeurs.',
                    fn (int $n) => [
                        [[$n, $n + 1], $n + 1],
                        [[-$n, $n], $n],
                        [[$n * 2, $n], $n * 2],
                    ],
                ),
                $this->template(
                    'longueur-texte',
                    'types-de-donnees',
                    'Compter les caractères',
                    'Écris solution(texte) pour retourner le nombre de caractères du texte.',
                    'function solution(texte) { /* retourne la longueur */ }',
                    ['Les chaînes possèdent une propriété de longueur.', 'Cette propriété s’appelle length.'],
                    'La propriété length donne le nombre de caractères de la chaîne.',
                    fn (int $n) => [
                        [['Code'.$n], strlen('Code'.$n)],
                        [[''], 0],
                        [['x'.str_repeat('a', $n)], $n + 1],
                    ],
                ),
                $this->template(
                    'produit',
                    'operateurs',
                    'Multiplier deux nombres',
                    'Écris solution(a, b) pour retourner le produit de deux nombres.',
                    'function solution(a, b) { return a * b; }',
                    ['Le produit correspond au résultat d’une multiplication.', 'Utilise l’opérateur *.'],
                    'L’opérateur * multiplie les deux nombres reçus.',
                    fn (int $n) => [
                        [[$n, 3], $n * 3],
                        [[-$n, 2], -$n * 2],
                        [[$n, 0], 0],
                    ],
                ),
            ],
            'medium' => [
                $this->template(
                    'somme-tableau',
                    'tableaux',
                    'Additionner les éléments d’un tableau',
                    'Écris solution(nombres) pour retourner la somme de tous les éléments du tableau.',
                    'function solution(nombres) { /* retourne la somme */ }',
                    ['Commence avec une somme égale à zéro.', 'Parcours chaque élément du tableau.'],
                    'Additionner les éléments dans un accumulateur permet de calculer la somme.',
                    fn (int $n) => [
                        [[$values = [$n, 1, 2]], array_sum($values)],
                        [[[]], 0],
                        [[[-$n, $n * 2, 4]], $n + 4],
                    ],
                ),
                $this->template(
                    'inverser-texte',
                    'types-de-donnees',
                    'Inverser une chaîne',
                    'Écris solution(texte) pour retourner les caractères dans l’ordre inverse.',
                    'function solution(texte) { /* retourne le texte inversé */ }',
                    ['Transforme le texte en tableau de caractères.', 'Utilise reverse puis join.'],
                    'Après inversion du tableau de caractères, join recompose la chaîne.',
                    fn (int $n) => [
                        [['Code'.$n], strrev('Code'.$n)],
                        [['abc'], 'cba'],
                        [[''], ''],
                    ],
                ),
                $this->template(
                    'compter-nombres-positifs',
                    'tableaux',
                    'Compter les nombres positifs',
                    'Écris solution(nombres) pour compter les valeurs strictement supérieures à zéro.',
                    'function solution(nombres) { /* retourne le nombre de positifs */ }',
                    ['Parcours toutes les valeurs.', 'Ignore zéro et les valeurs négatives.'],
                    'Une valeur est positive uniquement si elle est supérieure à zéro.',
                    fn (int $n) => [
                        [[[1, -$n, $n]], 2],
                        [[[0, -1, -2]], 0],
                        [[[2, 4, 6]], 3],
                    ],
                ),
                $this->template(
                    'maximum-tableau',
                    'tableaux',
                    'Trouver le maximum d’un tableau',
                    'Écris solution(nombres) pour retourner la plus grande valeur du tableau non vide.',
                    'function solution(nombres) { /* retourne le maximum */ }',
                    ['Initialise le maximum avec le premier élément.', 'Compare-le aux valeurs suivantes.'],
                    'Le maximum est la plus grande valeur rencontrée pendant le parcours.',
                    fn (int $n) => [
                        [[[$n, $n + 3, 2]], $n + 3],
                        [[[-$n, -1, -3]], -1],
                        [[[4, 4, 2]], 4],
                    ],
                ),
                $this->template(
                    'compter-nombres-pairs',
                    'boucles',
                    'Compter les nombres pairs',
                    'Écris solution(nombres) pour compter les nombres pairs dans un tableau.',
                    'function solution(nombres) { /* retourne le nombre de pairs */ }',
                    ['Parcours le tableau.', 'Un nombre pair a un reste nul après division par 2.'],
                    'Le reste modulo 2 permet de reconnaître chaque nombre pair.',
                    fn (int $n) => [
                        [[[2, 3, $n * 2]], 2],
                        [[[]], 0],
                        [[[-2, -1, 0, 1]], 2],
                    ],
                ),
            ],
            'hard' => [
                $this->template(
                    'valeurs-uniques',
                    'tableaux',
                    'Supprimer les doublons',
                    'Écris solution(valeurs) pour retourner les valeurs uniques en conservant leur ordre initial.',
                    'function solution(valeurs) { /* retourne les valeurs uniques */ }',
                    ['Un Set mémorise les valeurs déjà rencontrées.', 'Ajoute une valeur au résultat uniquement la première fois.'],
                    'Un ensemble permet de suivre les valeurs déjà ajoutées tout en préservant l’ordre du tableau.',
                    fn (int $n) => [
                        [[[1, $n, 1, 2, $n]], array_values(array_unique([1, $n, 1, 2, $n]))],
                        [[[3, 3, 3]], [3]],
                        [[[]], []],
                    ],
                ),
                $this->template(
                    'rotation-droite',
                    'tableaux',
                    'Faire pivoter un tableau',
                    'Écris solution(valeurs, k) pour déplacer les éléments de k positions vers la droite.',
                    'function solution(valeurs, k) { /* retourne le tableau pivoté */ }',
                    ['Réduis k avec la longueur du tableau.', 'Les derniers éléments deviennent les premiers.'],
                    'La rotation utilise le reste de k sur la longueur et déplace la fin du tableau au début.',
                    fn (int $n) => [
                        [[[1, 2, 3, 4], $n % 4], $this->rotate([1, 2, 3, 4], $n % 4)],
                        [[[1, 2, 3], 1], [3, 1, 2]],
                        [[[], 3], []],
                    ],
                ),
                $this->template(
                    'deuxieme-plus-grand',
                    'tableaux',
                    'Trouver le deuxième plus grand',
                    'Écris solution(nombres) pour retourner la deuxième plus grande valeur distincte.',
                    'function solution(nombres) { /* retourne le deuxième maximum distinct */ }',
                    ['Élimine les doublons avant de trier.', 'Le deuxième élément en partant de la fin est la réponse.'],
                    'Après avoir retiré les doublons, le deuxième élément du tableau trié décroissant est recherché.',
                    fn (int $n) => [
                        [[[1, $n + 2, $n, $n + 2]], $n],
                        [[[8, 3, 5, 1]], 5],
                        [[[-2, -4, -1]], -2],
                    ],
                ),
                $this->template(
                    'fusionner-tableaux-triés',
                    'tableaux',
                    'Fusionner deux tableaux triés',
                    'Écris solution(a, b) pour fusionner deux tableaux déjà triés en conservant l’ordre.',
                    'function solution(a, b) { /* retourne la fusion triée */ }',
                    ['Tu peux concaténer puis trier les deux tableaux.', 'Les nombres sont des entiers.'],
                    'La fusion doit contenir chaque élément puis les ordonner du plus petit au plus grand.',
                    fn (int $n) => [
                        [[[1, $n + 2], [2, $n + 3]], [1, 2, $n + 2, $n + 3]],
                        [[[], [1, 2]], [1, 2]],
                        [[[0, 4], []], [0, 4]],
                    ],
                ),
                $this->template(
                    'compter-mots',
                    'types-de-donnees',
                    'Compter les mots d’une phrase',
                    'Écris solution(phrase) pour retourner le nombre de mots séparés par des espaces.',
                    'function solution(phrase) { /* retourne le nombre de mots */ }',
                    ['Supprime les espaces au début et à la fin.', 'Une phrase vide contient zéro mot.'],
                    'Après suppression des espaces extérieurs, les mots se séparent sur les espaces.',
                    fn (int $n) => [
                        [['CodeQuest défi '.$n], 2],
                        [[''], 0],
                        [['  un   deux  trois  '], 3],
                    ],
                ),
            ],
            'expert' => [
                $this->template(
                    'aplatir-tableau',
                    'tableaux',
                    'Aplatir un tableau imbriqué',
                    'Écris solution(valeurs) pour aplatir récursivement un tableau de nombres imbriqués.',
                    'function solution(valeurs) { /* retourne un tableau aplati */ }',
                    ['Parcours récursivement les sous-tableaux.', 'Concatène les nombres dans l’ordre rencontré.'],
                    'Chaque sous-tableau est parcouru à son tour afin de produire une liste plate ordonnée.',
                    fn (int $n) => [
                        [[[$n, [$n + 1, [$n + 2]]]], [$n, $n + 1, $n + 2]],
                        [[[[], [1]], 2], [1, 2]],
                        [[[]], []],
                    ],
                ),
                $this->template(
                    'fibonacci',
                    'boucles',
                    'Calculer un terme de Fibonacci',
                    'Écris solution(n) pour retourner le terme numéro n de Fibonacci, avec F(0)=0 et F(1)=1.',
                    'function solution(n) { /* retourne le terme n */ }',
                    ['Traite séparément les deux premiers termes.', 'Calcule chaque terme avec les deux précédents.'],
                    'Chaque terme à partir de F(2) est la somme des deux termes précédents.',
                    fn (int $n) => [
                        [[($index = $n % 10)], $this->fibonacci($index)],
                        [[0], 0],
                        [[1], 1],
                    ],
                ),
                $this->template(
                    'palindrome',
                    'conditions',
                    'Vérifier un palindrome',
                    'Écris solution(texte) pour vérifier si le texte est identique lorsqu’il est lu à l’envers.',
                    'function solution(texte) { /* retourne true ou false */ }',
                    ['Compare le texte à sa version inversée.', 'Une chaîne vide est un palindrome.'],
                    'Un palindrome est identique à sa chaîne inversée.',
                    fn (int $n) => [
                        [['radar'], true],
                        [['code'], false],
                        [['a'.$n], false],
                    ],
                ),
                $this->template(
                    'mot-le-plus-long',
                    'tableaux',
                    'Trouver le mot le plus long',
                    'Écris solution(mots) pour retourner le premier mot ayant la plus grande longueur.',
                    'function solution(mots) { /* retourne le mot le plus long */ }',
                    ['Compare la longueur de chaque mot.', 'En cas d’égalité, garde le premier.'],
                    'Un parcours qui ne remplace le résultat que pour une longueur strictement supérieure conserve le premier mot en cas d’égalité.',
                    fn (int $n) => [
                        [[['Code', 'Quest'.$n, 'JS']], 'Quest'.$n],
                        [[['un', 'mot']], 'mot'],
                        [[['egal', 'test']], 'egal'],
                    ],
                ),
                $this->template(
                    'parentheses-valides',
                    'conditions',
                    'Valider les parenthèses',
                    'Écris solution(texte) pour vérifier que les parenthèses ouvrantes et fermantes sont correctement équilibrées.',
                    'function solution(texte) { /* retourne true ou false */ }',
                    ['Incrémente un compteur à chaque parenthèse ouvrante.', 'Le compteur ne doit jamais devenir négatif et doit finir à zéro.'],
                    'Une séquence est valide si chaque fermeture correspond à une ouverture précédente et si toutes les ouvertures sont fermées.',
                    fn (int $n) => [
                        [['('.str_repeat('()', $n % 4 + 1).')'], true],
                        [['(()'], false],
                        [['())('], false],
                    ],
                ),
            ],
        ];
    }

    private function template(
        string $slug,
        string $category,
        string $title,
        string $description,
        string $starterCode,
        array $hints,
        string $successExplanation,
        callable $cases,
    ): array {
        return [
            'slug' => $slug,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'starter_code' => $starterCode,
            'hints' => $hints,
            'success_explanation' => $successExplanation,
            'cases' => $cases,
        ];
    }

    private function rotate(array $values, int $positions): array
    {
        if ($values === []) {
            return [];
        }

        $positions %= count($values);

        return $positions === 0
            ? $values
            : array_merge(array_slice($values, -$positions), array_slice($values, 0, -$positions));
    }

    private function fibonacci(int $n): int
    {
        $previous = 0;
        $current = 1;

        for ($index = 0; $index < $n; $index++) {
            [$previous, $current] = [$current, $previous + $current];
        }

        return $previous;
    }
}
