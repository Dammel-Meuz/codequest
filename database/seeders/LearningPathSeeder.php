<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\LearningPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningPathSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $path = LearningPath::updateOrCreate(
                ['slug' => 'bases-javascript'],
                [
                    'title' => 'JavaScript : de zéro à l’asynchrone',
                    'description' => 'Une formation complète : des premières instructions jusqu’aux classes, au DOM, aux modules et aux appels d’API.',
                    'sort_order' => 1,
                    'is_published' => true,
                ]
            );

            $chapters = $this->chapters();
            $currentChapterSlugs = collect($chapters)->pluck('slug')->all();

            foreach ($chapters as $chapterData) {
                $exerciseSlugs = $chapterData['exercises'];
                $questions = $chapterData['questions'];
                unset($chapterData['exercises'], $chapterData['questions']);

                $chapter = $path->chapters()->updateOrCreate(
                    ['slug' => $chapterData['slug']],
                    $chapterData
                );

                if ($exerciseSlugs !== []) {
                    $exercises = Exercise::query()->whereIn('slug', $exerciseSlugs)->get();

                    if ($exercises->count() !== count($exerciseSlugs)) {
                        throw new \RuntimeException('Un ou plusieurs exercices du parcours sont introuvables.');
                    }

                    $exercises->each(fn (Exercise $exercise) => $exercise->update([
                        'learning_path_chapter_id' => $chapter->id,
                    ]));
                }

                foreach ($questions as $sortOrder => $questionData) {
                    $chapter->quizQuestions()->updateOrCreate(
                        ['sort_order' => $sortOrder],
                        [...$questionData, 'sort_order' => $sortOrder]
                    );
                }

                // Supprime les questions en trop si le chapitre en contient moins qu’avant
                $chapter->quizQuestions()
                    ->where('sort_order', '>=', count($questions))
                    ->whereDoesntHave('completions')
                    ->delete();
            }

            $path->chapters()
                ->whereNotIn('slug', $currentChapterSlugs)
                ->whereDoesntHave('quizQuestions.completions')
                ->delete();

            $this->syncPrerequisites();
        });
    }

    private function syncPrerequisites(): void
    {
        $prerequisites = [
            'affichage-sur-le-console' => ['afficher-un-message'],
            'multiplier-par-deux' => ['additionner-deux-nombres'],
            'verifier-nombre-pair' => ['additionner-deux-nombres'],
            'inverser-une-chaine' => ['compter-les-caracteres'],
        ];

        foreach ($prerequisites as $exerciseSlug => $prerequisiteSlugs) {
            $exercise = Exercise::where('slug', $exerciseSlug)->firstOrFail();
            $prerequisiteIds = Exercise::whereIn('slug', $prerequisiteSlugs)->pluck('id');
            $exercise->prerequisites()->sync($prerequisiteIds);
        }
    }

    /**
     * Construit une question à choix multiples.
     * $options : liste de 4 réponses, associées automatiquement aux lettres a à d.
     */
    private function q(string $prompt, array $options, string $correct, string $explanation, int $xp = 5): array
    {
        return [
            'prompt' => $prompt,
            'options' => array_combine(['a', 'b', 'c', 'd'], $options),
            'correct_option' => $correct,
            'explanation' => $explanation,
            'xp_reward' => $xp,
        ];
    }

    private function chapters(): array
    {
        return [
            // ─────────────────────────────────────────────
            // PARTIE 1 : Les fondamentaux
            // ─────────────────────────────────────────────
            [
                'slug' => 'premiers-pas',
                'title' => 'Premiers pas',
                'description' => 'Découvre les instructions et les sorties dans la console.',
                'sort_order' => 1,
                'course_content' => "JavaScript exécute les instructions de haut en bas. Une instruction décrit une action que le navigateur ou Node.js doit effectuer.\n\nPour afficher une information, utilise console.log(...). Exemple :\nconsole.log(\"Bonjour !\");\n\nLes chaînes de caractères s’écrivent entre guillemets simples ou doubles. Chaque appel à console.log affiche une ligne. Les commentaires commencent par // et ne sont pas exécutés.",
                'exercises' => ['afficher-un-message', 'affichage-sur-le-console'],
                'questions' => [
                    $this->q('Quelle instruction affiche le texte « Salut » dans la console ?', ['print("Salut")', 'console.log("Salut")', 'echo "Salut"', 'show("Salut")'], 'b', 'console.log(...) affiche la valeur fournie dans la console.'),
                    $this->q('Dans quel ordre JavaScript exécute-t-il normalement les instructions d’un fichier ?', ['De bas en haut', 'Dans un ordre aléatoire', 'De haut en bas', 'Les commentaires d’abord'], 'c', 'Sans structure particulière, les instructions sont exécutées dans l’ordre où elles apparaissent.'),
                    $this->q('Que fait une ligne qui commence par // ?', ['Elle est exécutée comme une instruction', 'Elle ajoute un commentaire ignoré par JavaScript', 'Elle affiche le reste de la ligne', 'Elle arrête le programme'], 'b', 'Le marqueur // commence un commentaire sur une ligne; son contenu n’est pas exécuté.'),
                    $this->q('Combien de lignes sont affichées par console.log("A"); console.log("B"); ?', ['Aucune', 'Une', 'Deux', 'Quatre'], 'c', 'Chaque appel à console.log affiche une ligne, donc ces deux appels produisent deux lignes.'),
                ],
            ],
            [
                'slug' => 'variables-et-types',
                'title' => 'Variables et types',
                'description' => 'Stocke des valeurs et reconnais les types de base.',
                'sort_order' => 2,
                'course_content' => "Une variable associe un nom à une valeur. Déclare une valeur réassignable avec let :\nlet score = 10;\nscore = 15;\n\nUtilise const quand la variable ne sera pas réassignée :\nconst langage = \"JavaScript\";\n\nLes types de base incluent number (42), string (\"texte\"), boolean (true ou false) et undefined. JavaScript détermine le type à partir de la valeur. typeof valeur permet d’inspecter certains types.",
                'exercises' => [],
                'questions' => [
                    $this->q('Quel mot-clé convient à une variable dont la valeur sera réassignée ?', ['let', 'const', 'fixed', 'varies'], 'a', 'let déclare une variable qui peut recevoir une nouvelle valeur.'),
                    $this->q('Quel est le type de la valeur true ?', ['string', 'number', 'boolean', 'object'], 'c', 'true et false sont les deux valeurs booléennes de JavaScript.'),
                    $this->q('Que retourne typeof 42 ?', ['"number"', '"integer"', '"string"', '"boolean"'], 'a', 'Les nombres entiers et décimaux ont tous le type number en JavaScript.'),
                    $this->q('Que se passe-t-il si on tente de réassigner une constante déclarée avec const ?', ['Sa valeur est remplacée', 'JavaScript l’ignore toujours', 'Une erreur est levée', 'Elle devient automatiquement une variable let'], 'c', 'const interdit la réassignation de la liaison après sa déclaration.'),
                ],
            ],
            [
                'slug' => 'operateurs',
                'title' => 'Opérateurs',
                'description' => 'Effectue des calculs et compare des valeurs.',
                'sort_order' => 3,
                'course_content' => "Les opérateurs arithmétiques principaux sont + (addition), - (soustraction), * (multiplication), / (division) et % (reste d’une division).\n\nExemple : 9 % 2 vaut 1, car il reste 1 après la division entière par 2.\n\nPour comparer des valeurs, préfère === et !== : ils comparent la valeur et le type. Les opérateurs <, >, <= et >= comparent des nombres ou des chaînes selon les règles JavaScript.",
                'exercises' => ['additionner-deux-nombres', 'multiplier-par-deux'],
                'questions' => [
                    $this->q('Que vaut 9 % 2 en JavaScript ?', ['4.5', '2', '1', '0'], 'c', 'L’opérateur % retourne le reste de la division : 9 divisé par 2 laisse un reste de 1.'),
                    $this->q('Quel opérateur teste une égalité stricte en JavaScript ?', ['=', '==', '===', '=>'], 'c', '=== compare sans conversion implicite, à la fois la valeur et le type.'),
                    $this->q('Que vaut 5 === "5" ?', ['true', 'false', '"5"', 'Une erreur de syntaxe'], 'b', 'La comparaison stricte retourne false car la valeur de gauche est un nombre et celle de droite une chaîne.'),
                    $this->q('Que vaut 8 / 2 ?', ['4', '16', '6', '0'], 'a', 'L’opérateur / effectue une division; 8 divisé par 2 vaut 4.'),
                ],
            ],
            [
                'slug' => 'conditions',
                'title' => 'Conditions',
                'description' => 'Fais choisir à ton programme le chemin adapté.',
                'sort_order' => 4,
                'course_content' => "Une condition exécute un bloc seulement si une expression est vraie :\nif (score >= 10) {\n  console.log(\"Réussi\");\n} else {\n  console.log(\"Continue\");\n}\n\nelse if permet de tester un autre cas. Les opérateurs logiques && (et), || (ou) et ! (non) combinent ou inversent des conditions. Une condition peut aussi être utilisée avec l’opérateur ternaire : condition ? valeurA : valeurB.",
                'exercises' => ['verifier-nombre-pair'],
                'questions' => [
                    $this->q('Quel bloc s’exécute lorsque la condition d’un if est fausse et qu’un else existe ?', ['Le bloc else', 'Les deux blocs', 'Le bloc if', 'Aucun bloc, toujours'], 'a', 'else fournit le chemin alternatif lorsque la condition du if est fausse.'),
                    $this->q('Quel opérateur logique signifie « les deux conditions doivent être vraies » ?', ['||', '&&', '!', '??'], 'b', '&& (ET logique) est vrai uniquement si ses deux côtés sont vrais.'),
                    $this->q('Si score vaut 12, quelle branche est exécutée dans if (score >= 10) ?', ['La branche dont la condition est vraie', 'La branche else uniquement', 'Les deux branches', 'Aucune branche'], 'a', '12 est supérieur ou égal à 10, donc le bloc du if est exécuté.'),
                    $this->q('Que vaut false || true ?', ['false', 'true', 'undefined', 'Une erreur'], 'b', '|| (OU logique) vaut true si au moins une de ses deux conditions vaut true.'),
                ],
            ],
            [
                'slug' => 'boucles',
                'title' => 'Boucles',
                'description' => 'Répète des instructions avec for et while.',
                'sort_order' => 5,
                'course_content' => "Une boucle répète un bloc d’instructions. Une boucle for est pratique quand le nombre de répétitions est connu :\nfor (let i = 0; i < 3; i++) {\n  console.log(i);\n}\n\nCe code affiche 0, 1 et 2. Une boucle while continue tant que sa condition est vraie. Pense à faire évoluer la variable de contrôle afin que la condition devienne fausse; sinon la boucle peut ne jamais s’arrêter. break quitte une boucle et continue passe à l’itération suivante.",
                'exercises' => [],
                'questions' => [
                    $this->q('Combien de fois le corps de cette boucle s’exécute-t-il ? for (let i = 0; i < 3; i++)', ['2', '3', '4', 'À l’infini'], 'b', 'La variable i prend successivement les valeurs 0, 1 et 2; la boucle s’arrête quand i vaut 3.'),
                    $this->q('Quand une boucle while s’arrête-t-elle ?', ['Quand sa condition devient fausse', 'Après exactement une répétition', 'Quand elle rencontre une variable', 'Elle ne s’arrête jamais'], 'a', 'while réévalue sa condition avant chaque répétition et s’arrête dès qu’elle est fausse.'),
                    $this->q('Quelle somme est calculée par for (let i = 1; i <= 3; i++) total += i ?', ['3', '5', '6', '7'], 'c', 'La boucle additionne 1, puis 2, puis 3; le total vaut 6.'),
                    $this->q('Que fait continue dans une boucle ?', ['Il quitte définitivement la boucle', 'Il passe directement à l’itération suivante', 'Il redémarre le programme', 'Il met la boucle en pause'], 'b', 'continue ignore la suite du bloc courant et passe à l’itération suivante.'),
                ],
            ],
            [
                'slug' => 'fonctions',
                'title' => 'Fonctions',
                'description' => 'Organise ton code en blocs réutilisables.',
                'sort_order' => 6,
                'course_content' => "Une fonction regroupe des instructions réutilisables. Elle peut recevoir des paramètres et renvoyer un résultat :\nfunction doubler(nombre) {\n  return nombre * 2;\n}\n\nconst resultat = doubler(4); // 8\n\nreturn termine l’exécution de la fonction et transmet sa valeur au code appelant. console.log affiche une valeur, mais ne la retourne pas. Une fonction peut aussi être écrite comme une fonction fléchée : const doubler = nombre => nombre * 2;",
                'exercises' => ['saluer-une-personne'],
                'questions' => [
                    $this->q('À quoi sert return dans une fonction ?', ['À afficher une ligne dans la console', 'À répéter la fonction', 'À renvoyer une valeur à l’appelant', 'À déclarer une constante'], 'c', 'return renvoie une valeur à l’endroit où la fonction a été appelée.'),
                    $this->q('Comment appelle-t-on la valeur transmise à une fonction dans son appel, par exemple saluer("Amina") ?', ['Un argument', 'Une boucle', 'Une condition', 'Une propriété'], 'a', 'Les paramètres sont les noms déclarés par la fonction; les arguments sont les valeurs fournies lors de l’appel.'),
                    $this->q('Dans function additionner(a, b) { return a + b; }, que sont a et b ?', ['Des arguments', 'Des paramètres', 'Des propriétés', 'Des tableaux'], 'b', 'a et b sont les paramètres déclarés dans la signature de la fonction.'),
                    $this->q('Que retourne une fonction qui se termine sans instruction return ?', ['false', '0', 'null', 'undefined'], 'd', 'En JavaScript, une fonction sans return explicite retourne undefined.'),
                ],
            ],
            [
                'slug' => 'tableaux-et-chaines',
                'title' => 'Tableaux et chaînes',
                'description' => 'Parcours des collections et transforme du texte.',
                'sort_order' => 7,
                'course_content' => "Un tableau ordonné se crée avec des crochets : const notes = [12, 15, 18]. Les indices commencent à 0; notes[0] vaut 12 et notes.length donne le nombre d’éléments.\n\nfor...of parcourt les valeurs. map crée un nouveau tableau transformé, filter conserve les éléments qui répondent à une condition et reduce accumule une valeur.\n\nUne chaîne est une suite de caractères. texte.length donne sa longueur. Pour inverser une chaîne, on peut la transformer en tableau de caractères, utiliser reverse(), puis join(\"\"). Les méthodes reverse et sort modifient le tableau sur lequel elles sont appelées.",
                'exercises' => ['compter-les-caracteres', 'somme-d-un-tableau', 'inverser-une-chaine'],
                'questions' => [
                    $this->q('Quel est le premier indice d’un tableau JavaScript ?', ['0', '1', '-1', 'Il n’y a pas d’indices'], 'a', 'Les indices des tableaux JavaScript commencent à zéro.'),
                    $this->q('Quelle méthode retourne un nouveau tableau en transformant chaque élément ?', ['filter', 'map', 'push', 'pop'], 'b', 'map applique une fonction à chaque élément et retourne un nouveau tableau.'),
                    $this->q('Si notes vaut [12, 15, 18], que retourne notes[notes.length - 1] ?', ['12', '15', '18', 'undefined'], 'c', 'length vaut 3 et le dernier indice est 3 - 1, soit 2; notes[2] vaut 18.'),
                    $this->q('Que fait filter sur un tableau ?', ['Il modifie chaque valeur', 'Il conserve les éléments qui satisfont une condition', 'Il ajoute un élément à la fin', 'Il retourne toujours un booléen'], 'b', 'filter retourne un nouveau tableau contenant les éléments pour lesquels le prédicat renvoie true.'),
                ],
            ],

            // ─────────────────────────────────────────────
            // PARTIE 2 : Données et JavaScript moderne
            // ─────────────────────────────────────────────
            [
                'slug' => 'objets-et-json',
                'title' => 'Objets et JSON',
                'description' => 'Regroupe des données liées et échange-les au format JSON.',
                'sort_order' => 8,
                'course_content' => <<<'TXT'
Un objet regroupe des données sous forme de paires clé/valeur :
const eleve = { nom: "Amina", age: 17 };

Lis une propriété avec eleve.nom ou eleve["age"]. Modifie-la avec eleve.age = 18, ajoute-en une avec eleve.ville = "Dakar" et supprime-la avec delete eleve.ville.

Un objet peut contenir des fonctions, appelées méthodes. Dans une méthode, this désigne l’objet courant :
const chien = { nom: "Rex", aboyer() { console.log(this.nom + " aboie"); } };

Object.keys(obj) retourne les clés, Object.values(obj) les valeurs et Object.entries(obj) des paires [clé, valeur].

JSON est un format texte pour échanger des données. JSON.stringify(objet) transforme un objet en texte et JSON.parse(texte) fait l’inverse. En JSON, les clés et les chaînes utilisent obligatoirement des guillemets doubles.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Comment lit-on la propriété nom d’un objet eleve ?', ['eleve->nom', 'eleve.nom', 'eleve::nom', 'eleve(nom)'], 'b', 'On accède à une propriété avec la notation pointée eleve.nom ou avec eleve["nom"].'),
                    $this->q('Quelle fonction transforme un objet JavaScript en chaîne JSON ?', ['JSON.parse', 'JSON.toString', 'JSON.stringify', 'JSON.encode'], 'c', 'JSON.stringify sérialise un objet en texte; JSON.parse fait l’opération inverse.'),
                    $this->q('Que retourne Object.keys({ a: 1, b: 2 }) ?', ['["a", "b"]', '[1, 2]', '"a,b"', '{ a, b }'], 'a', 'Object.keys retourne un tableau contenant les noms des propriétés.'),
                ],
            ],
            [
                'slug' => 'methodes-de-tableaux',
                'title' => 'Méthodes de tableaux',
                'description' => 'Transforme, filtre et agrège des données avec les méthodes modernes.',
                'sort_order' => 9,
                'course_content' => <<<'TXT'
Pour modifier un tableau : push ajoute à la fin, pop retire le dernier, unshift ajoute au début et shift retire le premier.

Pour chercher : includes(valeur) retourne true ou false, find(fn) retourne le premier élément qui satisfait la condition, findIndex(fn) retourne son indice, some(fn) vérifie qu’au moins un élément convient et every(fn) vérifie que tous conviennent.

Pour transformer sans modifier l’original :
const prix = [10, 20, 30];
const avecTaxe = prix.map(p => p * 1.18);
const chers = prix.filter(p => p > 15);
const total = prix.reduce((somme, p) => somme + p, 0);

slice(debut, fin) copie une portion du tableau. splice modifie le tableau d’origine.

sort() trie par défaut en comparant des chaînes. Pour trier des nombres, fournis une fonction de comparaison : nombres.sort((a, b) => a - b).

Les méthodes se chaînent : prix.filter(p => p > 15).map(p => p * 2).
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quelle méthode retourne le premier élément qui satisfait une condition ?', ['filter', 'map', 'some', 'find'], 'd', 'find retourne le premier élément pour lequel la fonction renvoie true, ou undefined.'),
                    $this->q('Que vaut [1, 2, 3, 4].filter(n => n % 2 === 0) ?', ['[1, 3]', '[2, 4]', '[4]', 'true'], 'b', 'filter conserve les éléments pairs : 2 et 4.'),
                    $this->q('Que vaut [1, 2, 3].reduce((acc, n) => acc + n, 0) ?', ['3', '[1, 2, 3]', '0', '6'], 'd', 'reduce accumule les valeurs en partant de 0 : 0 + 1 + 2 + 3 = 6.'),
                    $this->q('Pourquoi [10, 9, 1].sort() ne trie-t-il pas correctement les nombres ?', ['Il ne trie que les nombres négatifs', 'Il ne fonctionne pas avec les nombres', 'Il compare les éléments comme des chaînes par défaut', 'Il trie de la fin vers le début'], 'c', 'Sans fonction de comparaison, sort convertit les éléments en chaînes. Utilise (a, b) => a - b pour un tri numérique.'),
                ],
            ],
            [
                'slug' => 'javascript-moderne',
                'title' => 'JavaScript moderne (ES6+)',
                'description' => 'Adopte les syntaxes qui rendent le code plus court et plus lisible.',
                'sort_order' => 10,
                'course_content' => <<<'TXT'
Les gabarits de chaînes utilisent des accents graves et ${...} pour insérer une expression :
const message = `Bonjour ${prenom}, tu as ${age} ans.`;

La déstructuration extrait des valeurs :
const { nom, age } = eleve;
const [premier, deuxieme] = notes;

L’opérateur spread ... copie ou fusionne :
const copie = [...notes];
const fusion = { ...eleve, ville: "Dakar" };

L’opérateur rest regroupe les arguments restants :
function somme(...nombres) { return nombres.reduce((a, b) => a + b, 0); }

Un paramètre peut avoir une valeur par défaut : function saluer(nom = "invité") { ... }

Le chaînage optionnel ?. évite une erreur si une valeur est null ou undefined : utilisateur?.adresse?.ville.
L’opérateur ?? fournit une valeur de repli uniquement pour null et undefined : const quantite = saisie ?? 1.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quelle syntaxe permet d’insérer une variable dans une chaîne ?', ['"Bonjour {nom}"', 'Bonjour #{nom}', '`Bonjour ${nom}`', '\'Bonjour %nom%\''], 'c', 'Les gabarits de chaînes s’écrivent entre accents graves et insèrent une expression avec ${...}.'),
                    $this->q('Après const [a, b] = [1, 2], que vaut b ?', ['1', '2', 'undefined', '[1, 2]'], 'b', 'La déstructuration associe les valeurs aux variables selon leur position : a vaut 1 et b vaut 2.'),
                    $this->q('Que fait l’opérateur ?? ', ['Il retourne la valeur de droite seulement si celle de gauche est null ou undefined', 'Il retourne la valeur de droite si celle de gauche est fausse', 'Il compare deux valeurs strictement', 'Il inverse une condition'], 'a', '?? ne réagit qu’à null et undefined, contrairement à || qui réagit à toutes les valeurs « fausses » comme 0 ou "".'),
                ],
            ],
            [
                'slug' => 'portee-et-closures',
                'title' => 'Portée et closures',
                'description' => 'Comprends où vivent tes variables et comment une fonction s’en souvient.',
                'sort_order' => 11,
                'course_content' => <<<'TXT'
La portée détermine où une variable est accessible. let et const ont une portée de bloc : une variable déclarée dans { ... } n’existe que dans ce bloc.
if (true) {
  let secret = 42;
}
console.log(secret); // ReferenceError

var, plus ancien, a une portée de fonction et peut produire des comportements surprenants : préfère let et const.

Une variable lue avant sa déclaration avec let ou const provoque une ReferenceError (zone morte temporelle).

Une fonction peut lire les variables de la portée où elle a été créée. Quand elle les garde en mémoire après la fin de la fonction englobante, on parle de closure :
function creerCompteur() {
  let total = 0;
  return () => ++total;
}
const compteur = creerCompteur();
compteur(); // 1
compteur(); // 2

Limite le nombre de variables globales : elles sont accessibles partout et donc faciles à modifier par erreur.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Où une variable déclarée avec let dans un bloc { } est-elle visible ?', ['Dans tout le fichier', 'Uniquement dans ce bloc', 'Dans toutes les fonctions', 'Nulle part'], 'b', 'let et const ont une portée de bloc.'),
                    $this->q('Que se passe-t-il si on lit une const avant sa déclaration ?', ['Elle vaut undefined', 'Elle vaut 0', 'Une ReferenceError est levée', 'Rien, tout fonctionne'], 'c', 'Les variables let et const sont dans la zone morte temporelle jusqu’à leur déclaration.'),
                    $this->q('Qu’est-ce qu’une closure ?', ['Une fonction qui garde accès aux variables de la portée où elle a été créée', 'Une boucle qui se ferme toute seule', 'Une variable globale', 'Un objet immuable'], 'a', 'Une closure conserve une référence aux variables de sa portée d’origine, même après la fin de la fonction englobante.'),
                ],
            ],
            [
                'slug' => 'gestion-des-erreurs',
                'title' => 'Gestion des erreurs et débogage',
                'description' => 'Anticipe les erreurs et apprends à les traquer.',
                'sort_order' => 12,
                'course_content' => <<<'TXT'
Une erreur non gérée arrête le programme. try/catch permet de réagir :
try {
  const donnees = JSON.parse(texte);
} catch (erreur) {
  console.error("JSON invalide :", erreur.message);
} finally {
  console.log("Toujours exécuté");
}

Le bloc finally s’exécute que l’erreur survienne ou non.

Tu peux lever tes propres erreurs avec throw :
if (age < 0) {
  throw new Error("L’âge ne peut pas être négatif");
}

Types d’erreurs courants : SyntaxError (code mal écrit), ReferenceError (variable inexistante), TypeError (opération impossible sur ce type, par exemple appeler undefined comme une fonction).

Pour déboguer : lis le message et le numéro de ligne de la console, affiche des valeurs avec console.log ou console.table, et utilise les outils de développement du navigateur (points d’arrêt, instruction debugger).
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quel bloc s’exécute toujours, qu’il y ait une erreur ou non ?', ['try', 'catch', 'finally', 'throw'], 'c', 'finally est exécuté dans tous les cas, ce qui est utile pour nettoyer ou terminer une action.'),
                    $this->q('Comment lever volontairement une erreur ?', ['raise Error("...")', 'throw new Error("...")', 'error("...")', 'return Error("...")'], 'b', 'throw interrompt l’exécution et transmet l’erreur au catch le plus proche.'),
                    $this->q('Quelle erreur est levée quand on lit une variable qui n’existe pas ?', ['SyntaxError', 'TypeError', 'RangeError', 'ReferenceError'], 'd', 'ReferenceError indique l’utilisation d’un nom qui n’a pas été déclaré.'),
                ],
            ],

            // ─────────────────────────────────────────────
            // PARTIE 3 : JavaScript dans le navigateur
            // ─────────────────────────────────────────────
            [
                'slug' => 'manipuler-le-dom',
                'title' => 'Manipuler le DOM',
                'description' => 'Lis et modifie une page web avec JavaScript.',
                'sort_order' => 13,
                'course_content' => <<<'TXT'
Le DOM est la représentation de la page HTML sous forme d’arbre d’objets que JavaScript peut lire et modifier.

Sélectionne des éléments avec des sélecteurs CSS :
const titre = document.querySelector("h1");
const items = document.querySelectorAll(".item");

Modifie le contenu :
titre.textContent = "Nouveau titre";

Préfère textContent à innerHTML pour du texte : innerHTML interprète le HTML et peut ouvrir une faille XSS si la valeur vient d’un utilisateur.

Gère les classes avec classList :
titre.classList.add("actif");
titre.classList.remove("actif");
titre.classList.toggle("actif");

Crée et insère un élément :
const li = document.createElement("li");
li.textContent = "Tâche";
document.querySelector("ul").append(li);

Place ton script en fin de body ou utilise l’attribut defer pour que le DOM soit prêt avant l’exécution.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quelle méthode retourne le premier élément correspondant à un sélecteur CSS ?', ['document.querySelector', 'document.getAll', 'document.find', 'document.select'], 'a', 'querySelector accepte n’importe quel sélecteur CSS et retourne le premier élément trouvé.'),
                    $this->q('Quelle propriété est la plus sûre pour insérer du texte brut ?', ['innerHTML', 'outerHTML', 'textContent', 'value'], 'c', 'textContent insère le texte sans l’interpréter comme du HTML, ce qui évite les injections.'),
                    $this->q('Comment ajouter la classe « actif » à un élément ?', ['element.class = "actif"', 'element.classList.add("actif")', 'element.addClass("actif")', 'element.style.add("actif")'], 'b', 'classList.add ajoute une classe sans toucher aux autres.'),
                ],
            ],
            [
                'slug' => 'evenements',
                'title' => 'Événements',
                'description' => 'Fais réagir la page aux actions de l’utilisateur.',
                'sort_order' => 14,
                'course_content' => <<<'TXT'
Un événement est un signal envoyé par le navigateur : clic, saisie au clavier, envoi de formulaire, chargement…

Attache un gestionnaire avec addEventListener :
const bouton = document.querySelector("#valider");
bouton.addEventListener("click", () => {
  console.log("Cliqué !");
});

Le gestionnaire reçoit un objet event. event.target désigne l’élément à l’origine de l’événement.

Pour un formulaire, empêche le rechargement de la page :
formulaire.addEventListener("submit", (event) => {
  event.preventDefault();
  // traiter les données
});

L’événement input se déclenche à chaque modification d’un champ; change se déclenche à la validation de la modification.

La délégation d’événements consiste à écouter sur un parent pour gérer ses enfants, y compris ceux ajoutés plus tard :
liste.addEventListener("click", (event) => {
  if (event.target.matches("button")) { /* ... */ }
});

removeEventListener retire un gestionnaire (il faut lui passer la même fonction).
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quelle méthode attache un gestionnaire d’événement à un élément ?', ['element.onEvent()', 'element.listen()', 'element.bind()', 'element.addEventListener()'], 'd', 'addEventListener(type, fonction) enregistre un gestionnaire pour un type d’événement.'),
                    $this->q('À quoi sert event.preventDefault() sur un événement submit ?', ['À supprimer le formulaire', 'À empêcher l’envoi et le rechargement par défaut', 'À vider les champs', 'À valider automatiquement les données'], 'b', 'preventDefault annule le comportement par défaut du navigateur pour cet événement.'),
                    $this->q('Quelle propriété de event désigne l’élément sur lequel l’événement s’est produit ?', ['event.source', 'event.element', 'event.target', 'event.node'], 'c', 'event.target référence l’élément à l’origine de l’événement.'),
                ],
            ],

            // ─────────────────────────────────────────────
            // PARTIE 4 : Structurer son code
            // ─────────────────────────────────────────────
            [
                'slug' => 'classes-et-poo',
                'title' => 'Classes et programmation orientée objet',
                'description' => 'Modélise des concepts avec des classes et de l’héritage.',
                'sort_order' => 15,
                'course_content' => <<<'TXT'
Une classe est un modèle pour créer des objets :
class Animal {
  constructor(nom) {
    this.nom = nom;
  }
  parler() {
    console.log(`${this.nom} fait du bruit`);
  }
}
const a = new Animal("Rex");
a.parler();

constructor est appelé automatiquement avec new pour initialiser l’instance.

L’héritage avec extends réutilise une classe existante. super appelle le constructeur ou les méthodes du parent :
class Chien extends Animal {
  constructor(nom, race) {
    super(nom);
    this.race = race;
  }
  parler() {
    console.log(`${this.nom} aboie`);
  }
}

static définit une méthode appartenant à la classe elle-même : Chien.creerInconnu().

Un champ précédé de # est privé et inaccessible depuis l’extérieur de la classe :
class Compte { #solde = 0; deposer(m) { this.#solde += m; } }
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quel mot-clé crée une instance d’une classe ?', ['create', 'new', 'make', 'instance'], 'b', 'new appelle le constructeur et retourne un nouvel objet.'),
                    $this->q('Comment appelle-t-on le constructeur de la classe parente dans une classe fille ?', ['parent()', 'base()', 'this.parent()', 'super()'], 'd', 'super(...) doit être appelé dans le constructeur d’une classe fille avant d’utiliser this.'),
                    $this->q('Que signifie un champ comme #solde dans une classe ?', ['Un champ en lecture seule', 'Un champ privé accessible uniquement dans la classe', 'Un champ statique', 'Un commentaire'], 'b', 'Le préfixe # déclare un champ privé, inaccessible depuis l’extérieur de la classe.'),
                ],
            ],
            [
                'slug' => 'modules',
                'title' => 'Modules',
                'description' => 'Découpe ton code en fichiers réutilisables.',
                'sort_order' => 16,
                'course_content' => <<<'TXT'
Un module est un fichier JavaScript qui expose certaines parties de son code et en importe d’autres.

Dans maths.js :
export function additionner(a, b) { return a + b; }
export const PI = 3.14159;
export default function multiplier(a, b) { return a * b; }

Dans main.js :
import multiplier, { additionner, PI } from "./maths.js";

Les exports nommés s’importent entre accolades avec leur nom exact. Un module n’a qu’un seul export par défaut, importé sans accolades et sous le nom de ton choix.

Dans le navigateur, charge le point d’entrée avec :
<script type="module" src="main.js"></script>

Les modules ont leur propre portée : leurs variables ne polluent pas l’espace global. Ils sont exécutés une seule fois, même importés à plusieurs endroits.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Comment charger un script comme module dans le navigateur ?', ['<script module>', '<script type="module">', '<script import>', '<module src="...">'], 'b', 'L’attribut type="module" active la syntaxe import/export.'),
                    $this->q('Quelle ligne importe la fonction nommée additionner depuis maths.js ?', ['import additionner from "./maths.js"', 'require additionner "./maths.js"', 'import { additionner } from "./maths.js"', 'using additionner from "./maths.js"'], 'c', 'Les exports nommés s’importent entre accolades.'),
                    $this->q('Combien d’exports par défaut un module peut-il avoir ?', ['Aucun', 'Un seul', 'Deux', 'Autant qu’on veut'], 'b', 'Un module ne peut déclarer qu’un seul export default.'),
                ],
            ],

            // ─────────────────────────────────────────────
            // PARTIE 5 : Asynchrone et API
            // ─────────────────────────────────────────────
            [
                'slug' => 'asynchrone-et-promesses',
                'title' => 'Asynchrone et promesses',
                'description' => 'Comprends comment JavaScript gère les opérations qui prennent du temps.',
                'sort_order' => 17,
                'course_content' => <<<'TXT'
JavaScript exécute le code un morceau à la fois, mais il peut programmer des actions pour plus tard sans bloquer le reste.

setTimeout exécute une fonction après un délai :
console.log("A");
setTimeout(() => console.log("B"), 0);
console.log("C");
// Affiche A, C, puis B

Même avec 0 ms, B passe après le code synchrone en cours.

Une promesse représente un résultat futur. Elle a trois états : pending (en attente), fulfilled (réussie) et rejected (échouée).
const attendre = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

attendre(1000)
  .then(() => console.log("Une seconde écoulée"))
  .catch((erreur) => console.error(erreur))
  .finally(() => console.log("Terminé"));

Promise.all([p1, p2]) attend que toutes les promesses réussissent; elle échoue dès que l’une échoue. Promise.allSettled attend que toutes se terminent, quel que soit le résultat.

Les callbacks imbriqués deviennent vite illisibles : les promesses permettent de chaîner les étapes proprement.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quels sont les trois états d’une promesse ?', ['start, run, stop', 'pending, fulfilled, rejected', 'open, closed, locked', 'new, active, done'], 'b', 'Une promesse est en attente (pending), puis réussie (fulfilled) ou échouée (rejected).', 10),
                    $this->q('Dans quel ordre s’affichent les lettres ? console.log("A"); setTimeout(() => console.log("B"), 0); console.log("C");', ['A, B, C', 'B, A, C', 'C, B, A', 'A, C, B'], 'd', 'Le code synchrone (A puis C) s’exécute d’abord; la fonction de setTimeout est exécutée ensuite.', 10),
                    $this->q('Quelle méthode attend que plusieurs promesses soient toutes réussies ?', ['Promise.all', 'Promise.race', 'Promise.first', 'Promise.wait'], 'a', 'Promise.all retourne une promesse qui réussit quand toutes les promesses réussissent.', 10),
                ],
            ],
            [
                'slug' => 'async-await-et-fetch',
                'title' => 'async/await et fetch',
                'description' => 'Récupère des données depuis une API avec un code lisible.',
                'sort_order' => 18,
                'course_content' => <<<'TXT'
async/await est une écriture plus lisible des promesses. Une fonction async retourne toujours une promesse, et await met la fonction en pause jusqu’à ce que la promesse soit résolue :
async function chargerUtilisateurs() {
  try {
    const reponse = await fetch("https://jsonplaceholder.typicode.com/users");
    if (!reponse.ok) {
      throw new Error("Erreur HTTP " + reponse.status);
    }
    const utilisateurs = await reponse.json();
    return utilisateurs;
  } catch (erreur) {
    console.error("Chargement impossible :", erreur.message);
  }
}

fetch ne rejette pas la promesse pour une réponse 404 ou 500 : elle n’échoue que pour un problème réseau. Vérifie donc toujours reponse.ok.

reponse.json() lit le corps de la réponse et le convertit en objet JavaScript.

Envoyer des données :
await fetch("/api/taches", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ titre: "Réviser" }),
});

Pour lancer plusieurs requêtes en parallèle, combine await avec Promise.all.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Que retourne toujours une fonction déclarée avec async ?', ['Une chaîne', 'Un tableau', 'Un objet JSON', 'Une promesse'], 'd', 'Une fonction async enveloppe sa valeur de retour dans une promesse.', 10),
                    $this->q('Que se passe-t-il avec fetch quand le serveur répond 404 ?', ['La promesse est rejetée', 'La promesse réussit; il faut vérifier response.ok', 'Le navigateur affiche une erreur et s’arrête', 'fetch réessaie automatiquement'], 'b', 'fetch ne rejette que pour les erreurs réseau; les statuts 4xx et 5xx doivent être testés avec response.ok.', 10),
                    $this->q('Quelle méthode lit le corps d’une réponse fetch au format JSON ?', ['response.parse()', 'response.text.json', 'response.json()', 'response.body()'], 'c', 'response.json() retourne une promesse qui se résout avec l’objet JavaScript.', 10),
                ],
            ],
            [
                'slug' => 'tests-automatises',
                'title' => 'Tests automatisés et qualité du code',
                'description' => 'Vérifie le comportement de tes fonctions et détecte les régressions.',
                'sort_order' => 19,
                'course_content' => <<<'TXT'
Un test automatisé vérifie qu’un programme produit le résultat attendu pour une entrée donnée. Il rend les erreurs plus faciles à repérer et protège le code contre les régressions.

Un test unitaire cible une petite fonction isolée. Avec les modules intégrés de Node.js :
import test from "node:test";
import assert from "node:assert/strict";

test("additionner deux nombres", () => {
  assert.equal(additionner(2, 3), 5);
});

Un test comporte généralement trois étapes : préparer les données, appeler le code, puis vérifier le résultat. Teste aussi les limites : tableau vide, zéro, valeurs négatives et chaîne vide.

Choisis une assertion adaptée. assert.equal compare une valeur simple; assert.deepEqual compare la structure de tableaux ou d’objets. Un test doit échouer clairement si le résultat n’est pas celui attendu.

Garde les tests indépendants : chacun prépare ses propres données et ne dépend pas de l’ordre d’exécution des autres tests. Lance-les après chaque modification importante et avant de partager le code.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quel est le rôle principal d’un test automatisé ?', ['Remplacer le code source', 'Vérifier automatiquement un comportement attendu', 'Accélérer Internet', 'Créer une interface graphique'], 'b', 'Un test exécute le code avec des données définies et vérifie que le résultat correspond au comportement attendu.'),
                    $this->q('Quel cas est particulièrement utile à tester en plus du cas habituel ?', ['Uniquement une valeur aléatoire', 'Un cas limite comme un tableau vide', 'Seulement le nom de la fonction', 'Le commentaire du fichier'], 'b', 'Les cas limites révèlent souvent des erreurs qui n’apparaissent pas avec des entrées ordinaires.'),
                    $this->q('Quelle assertion convient pour comparer le contenu de deux tableaux ?', ['assert.deepEqual', 'console.log', 'setTimeout', 'JSON.stringify uniquement'], 'a', 'Une comparaison profonde vérifie les valeurs et la structure des tableaux, pas seulement leur identité.'),
                    $this->q('Pourquoi les tests doivent-ils être indépendants les uns des autres ?', ['Pour qu’ils ne dépendent ni de l’ordre ni de l’état laissé par un autre test', 'Pour qu’ils s’exécutent uniquement dans le navigateur', 'Pour éviter toute assertion', 'Pour partager les mêmes variables globales'], 'a', 'Des tests indépendants sont plus fiables, reproductibles et faciles à exécuter séparément.'),
                ],
            ],
            [
                'slug' => 'bonnes-pratiques-et-projet-final',
                'title' => 'Bonnes pratiques et projet final',
                'description' => 'Écris du code propre et assemble tout ce que tu as appris.',
                'sort_order' => 20,
                'course_content' => <<<'TXT'
Bonnes pratiques :
- Utilise const par défaut, let si la valeur change, et évite var.
- Choisis des noms explicites : calculerTotal vaut mieux que ct.
- Écris des fonctions courtes qui font une seule chose.
- Compare avec === et !==.
- Limite les variables globales et découpe ton code en modules.
- Commente le pourquoi, pas le quoi.
- Gère les erreurs des opérations qui peuvent échouer (réseau, JSON, saisie utilisateur).

Stocker des données dans le navigateur : localStorage garde uniquement des chaînes de caractères, il faut donc convertir :
localStorage.setItem("taches", JSON.stringify(taches));
const taches = JSON.parse(localStorage.getItem("taches")) ?? [];

Projet final : une application de liste de tâches.
1. Un formulaire pour ajouter une tâche (événement submit, preventDefault).
2. Un affichage dynamique de la liste (createElement, textContent).
3. Un bouton pour marquer une tâche comme terminée ou la supprimer (délégation d’événements).
4. Une sauvegarde dans localStorage au rechargement de la page.
5. Un filtre : toutes, à faire, terminées (filter).
6. Pour aller plus loin : charger des tâches de départ avec fetch et organiser le code en modules.
TXT,
                'exercises' => [],
                'questions' => [
                    $this->q('Quel type de données localStorage stocke-t-il ?', ['Des chaînes de caractères uniquement', 'Des objets JavaScript', 'Des fonctions', 'Des promesses'], 'a', 'localStorage ne stocke que du texte : on utilise JSON.stringify et JSON.parse pour les objets et les tableaux.'),
                    $this->q('Quelle déclaration faut-il privilégier par défaut ?', ['var', 'let', 'const', 'global'], 'c', 'const exprime l’intention de ne pas réassigner la variable; on passe à let seulement si nécessaire.'),
                    $this->q('Pourquoi découper son code en petites fonctions ?', ['Pour que le code soit plus lent', 'Pour améliorer lisibilité, réutilisation et tests', 'Parce que JavaScript l’impose', 'Pour éviter d’utiliser des variables'], 'b', 'Des fonctions courtes et ciblées sont plus faciles à comprendre, à réutiliser et à tester.'),
                ],
            ],
        ];
    }
}