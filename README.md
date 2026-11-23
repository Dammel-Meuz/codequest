<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Moteur de correction isolé

Les soumissions JavaScript s’exécutent dans un conteneur Docker dédié, et non dans le processus Laravel. Avant de lancer les workers de queue, construis l’image du runner :

```powershell
docker build -f docker\code-runner\Dockerfile -t codequest-runner:node24 docker\code-runner
```

Configure `CODE_EXECUTION_DOCKER_BINARY` et `CODE_EXECUTION_IMAGE` dans l’environnement Laravel si les valeurs par défaut ne conviennent pas. Le worker de queue doit pouvoir joindre le démon Docker et l’image doit déjà être disponible localement; le runner ne télécharge pas d’image au moment d’une soumission.

Chaque cas de test est lancé dans un conteneur sans réseau, avec un système de fichiers racine en lecture seule et des limites de ressources et de sortie. Le code soumis et l’entrée du cas courant sont transmis via l’entrée standard vers un fichier temporaire dans le `tmpfs` du conteneur, supprimé avec celui-ci. Le résultat attendu reste côté Laravel et n’est pas transmis au conteneur. L’image du runner doit être reconstruite après une modification de `docker\code-runner`. L’installation Docker qui héberge le worker doit être dédiée et protégée : l’accès au démon Docker est un privilège élevé. Pour un service public à haut risque, préfère un worker dédié avec une isolation renforcée (par exemple gVisor, Kata Containers ou une micro-VM).

Les appels de contrôle au démon Docker peuvent prendre jusqu’à 15 secondes; la limite totale de correction d’une soumission demeure 45 secondes.

## Accompagnement pédagogique

Dans l’administration, chaque exercice peut contenir jusqu’à cinq indices et une explication affichée après une réussite. Les indices sont révélés un par un à la demande de l’étudiant. Les résultats des tests visibles aident à comprendre un échec; pour un test caché, seul le statut réussi/échoué apparaît, sans divulguer son entrée ni ses résultats attendus ou obtenus.

## Parcours d’apprentissage

Les étudiants retrouvent les parcours publiés dans la page **Apprentissage** de la navigation, avec recherche, filtres d’avancement et progression chapitre par chapitre. Les administrateurs créent et publient les parcours et leurs chapitres depuis **Administration → Parcours**, puis associent les exercices à un chapitre et définissent leurs prérequis dans le formulaire d’exercice. Le lien **Gérer le cours et les QCM** permet d’ajouter une leçon et ses questions à chaque chapitre. Les bonnes réponses sont vérifiées côté serveur; chaque question récompense ses XP une seule fois et les chapitres suivants restent verrouillés jusqu’à la réussite des QCM précédents. Le tableau de bord étudiant suit séparément l’avancement des cours et des exercices. Le classement hebdomadaire inclut les XP des QCM; les classements par catégorie restent basés sur les exercices, car les leçons ne sont pas associées à une catégorie.

Le parcours de démonstration JavaScript comprend 20 chapitres progressifs, des leçons illustrées et 69 QCM avec explications. Il va des premières instructions aux tests automatisés et au projet final. Après migration, charge les données de démonstration avec `php artisan db:seed`.

## Quête hebdomadaire

Le tableau de bord propose une quête « Réussis 3 exercices différents cette semaine ». La progression s’appuie sur les réussites uniques enregistrées entre le lundi et le dimanche, selon le fuseau de l’application. Une récompense bonus de 30 XP est attribuée une seule fois à la complétion. La quête de la semaine est créée automatiquement lors d’une réussite d’exercice ou de l’affichage du tableau de bord.

## Exercices supplémentaires

Pour disposer de 100 exercices par difficulté (facile, moyen, difficile et expert), exécute ensuite :

```sh
php artisan db:seed --class="Database\Seeders\ExerciseDifficultySeeder"
```

Ce seeder complète les exercices existants sans les supprimer, ajoute trois cas de test par exercice généré (dont un caché) et peut être relancé sans créer de doublons.

## Connexion avec Google et GitHub

CodeQuest utilise Laravel Socialite pour proposer ces deux fournisseurs sur la page de connexion. Crée une application OAuth chez chaque fournisseur et configure les variables suivantes dans `.env` : `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET` et `GITHUB_REDIRECT_URI`. Les URL de rappel doivent correspondre à `https://ton-domaine/auth/google/callback` et `https://ton-domaine/auth/github/callback` (en local, remplace le domaine par l’URL configurée dans `APP_URL`). GitHub doit autoriser la portée `user:email`.

Après configuration, exécute `php artisan migrate`. Un compte social nouveau est créé avec le rôle étudiant; une adresse vérifiée qui correspond à un compte existant permet de le relier sans modifier son rôle ni son mot de passe. Les adresses non vérifiées ne peuvent pas servir à créer ou relier un compte.

Si Socialite renvoie `InvalidStateException`, le navigateur n’a probablement pas renvoyé la session créée avant la redirection. Vérifie que `APP_URL` et les URL de rappel utilisent exactement le même domaine et HTTPS, que `SESSION_DOMAIN` est vide pour un cookie limité à l’hôte (ou correspond au domaine partagé souhaité), et que les requêtes HTTPS transmises par ton reverse proxy sont correctement reconnues par Laravel. En production, configure `SESSION_SECURE_COOKIE=true`, conserve `SESSION_SAME_SITE=lax`, utilise un stockage de sessions persistant et partagé si plusieurs instances de l’application sont utilisées, puis exécute `php artisan config:clear` (ou reconstruis le cache de configuration). Ne désactive pas la validation `state` avec `stateless()` : elle protège le flux OAuth contre les attaques CSRF.

## Vérification de l’adresse e-mail

À l’inscription, CodeQuest envoie un lien Laravel signé et demande à l’étudiant de confirmer son adresse avant d’accéder aux exercices, aux cours ou au tableau de bord. Le lien peut être renvoyé depuis l’écran de vérification; il expire selon la durée Laravel configurée et les renvois sont limités en fréquence.

Configure un transport mail avant d’utiliser la vérification en production (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` et `MAIL_FROM_ADDRESS`). `APP_URL` doit correspondre au domaine public pour que les liens signés soient valides. En développement, avec `MAIL_MAILER=log`, le lien est écrit dans `storage/logs/laravel.log`.

## Gestion du mot de passe

Le lien **Mot de passe oublié ?** de la connexion envoie un lien de réinitialisation Laravel, valable 60 minutes par défaut et à usage unique. L’étudiant peut également changer son mot de passe dans **Mon profil** après avoir confirmé le mot de passe actuel. Les notifications de vérification et de réinitialisation utilisent le gabarit e-mail CodeQuest (logo, couleurs violet/cyan et mise en page compatible avec les clients mail); la configuration SMTP décrite ci-dessus est nécessaire pour les envoyer réellement.

## Compte administrateur initial

`php artisan db:seed` crée également le compte `ndoyemamemoussa@gmail.com`, avec le rôle administrateur et l’adresse vérifiée. Aucun mot de passe par défaut n’est enregistré dans le code : pour une nouvelle base, utilise **Mot de passe oublié ?** sur la page de connexion afin d’en définir un. En local avec `MAIL_MAILER=log`, le lien de réinitialisation est disponible dans `storage/logs/laravel.log`. Si ce compte existe déjà, le seeder conserve son mot de passe et ses autres données, tout en garantissant le rôle administrateur.

## Brouillons d’exercices

Le code écrit dans Monaco est sauvegardé automatiquement par étudiant et par exercice, puis restauré à la prochaine visite. Chaque soumission conserve séparément le code exact envoyé à la correction; l’étudiant peut consulter ce code et le restaurer dans son brouillon.

## Suivi administratif

Le tableau de bord d’administration présente les taux calculés sur les soumissions corrigées, les échecs par exercice, les tentatives sans réussite enregistrée, l’avancement des étudiants et les dernières soumissions. Un exercice est signalé à examiner à partir de cinq corrections terminées si son taux de réussite est inférieur à 50 %; ce repère est indicatif et ne remplace pas une analyse pédagogique. Le code soumis n’est pas affiché dans l’activité récente.

## Exploitation en production

### Configuration et déploiement

- Utilise `APP_ENV=production`, `APP_DEBUG=false`, une `APP_URL` HTTPS, et conserve la même `APP_KEY` secrète entre les déploiements. Ne versionne jamais `.env`.
- Choisis une base de données de production sauvegardée et partagée entre les instances. SQLite local n’est pas adapté à plusieurs serveurs web/workers.
- Configure un backend de queue durable (`database` ou Redis). Les migrations créent `jobs` et `failed_jobs`.
- Pour les cookies de session servis en HTTPS, configure `SESSION_SECURE_COOKIE=true`; conserve `SESSION_HTTP_ONLY=true` et `SESSION_SAME_SITE=lax`.
- Configure un canal de logs avec rotation (`LOG_CHANNEL=daily`, `LOG_LEVEL=warning`, `LOG_DAILY_DAYS=14`) et transfère les erreurs vers la supervision de l’hébergeur.
- Configure un transport mail opérationnel; `MAIL_MAILER=log` est réservé au développement.

Après avoir installé les dépendances et configuré les secrets sur le serveur :

```sh
php artisan migrate --force
npm ci
npm run build
php artisan optimize
docker build -f docker/code-runner/Dockerfile -t codequest-runner:node24 docker/code-runner
```

Le compte du worker doit pouvoir joindre le démon Docker et écrire dans `storage` et `bootstrap/cache`, sans rendre le reste du code modifiable par le processus web. L’accès au socket Docker équivaut à un privilège administrateur : utilise un worker dédié, limite les comptes autorisés et ne monte pas le socket dans le serveur web public.

### Workers et file d’échecs

Supervise en continu un worker Laravel et redémarre-le automatiquement après un arrêt ou un déploiement. Pour le backend `database`, la commande de base est :

```sh
php artisan queue:work database --queue=default --sleep=1 --tries=3 --timeout=60 --max-time=3600
```

La connexion de queue doit conserver un `retry_after` **supérieur** au timeout du job (60 secondes; le défaut actuel est 90) afin d’éviter que le même travail soit repris pendant son exécution. Lors d’un déploiement, exécute `php artisan queue:restart` après le remplacement du code; le superviseur doit relancer le worker. Surveille la taille de la file ainsi que `php artisan queue:failed`; après analyse, une tâche peut être relancée avec `php artisan queue:retry <id>`. Le job de correction consigne le statut, le nombre de tests et la durée, sans inclure le code soumis dans ces logs.

### Sauvegardes et restauration

Active les sauvegardes automatiques de la base via l’hébergeur ou le moteur utilisé. Stocke-les chiffrées, hors du serveur applicatif, avec une rétention définie; protège également les pièces nécessaires à la restauration. Pour une base SQLite sur un hôte Linux, crée une copie cohérente avec `sqlite3 database/database.sqlite ".backup '/chemin-prive/codequest.sqlite'"` plutôt qu’une copie de fichier pendant que la base est active. Pour MySQL/PostgreSQL, utilise l’outil de sauvegarde du moteur ou les snapshots gérés par l’hébergeur.

Documente la fréquence et le responsable des sauvegardes, puis teste régulièrement une restauration sur un environnement isolé. Ne restaure pas une sauvegarde par-dessus la base de production comme test.

### Données publiques et contrôle d’accès

Les pages administratives restent protégées par le middleware `admin`; les profils étudiants et classements ne doivent exposer que le nom public, le niveau, les XP et les récompenses. Ne place jamais e-mail, code soumis, données d’authentification ou détails de tests cachés dans les vues publiques. Vérifie également `php artisan route:list` et les comptes/permissions de déploiement avant chaque mise en production.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
