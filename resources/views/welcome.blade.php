<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Apprends à programmer en relevant des défis, en gagnant de l’expérience et en progressant à ton rythme avec CodeQuest.">
    <meta name="theme-color" content="#0b1020">
    <title>CodeQuest — Le code devient ton terrain de jeu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1020] text-slate-100 antialiased">
    <a href="#contenu"
       class="sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-slate-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Aller au contenu principal
    </a>

    <header class="sticky top-0 z-40 border-b border-white/5 bg-[#0B1020]/90 backdrop-blur-xl">
        <nav aria-label="Navigation principale" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="CodeQuest, accueil">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-cyan-400 text-sm font-black text-white shadow-lg shadow-violet-500/20" aria-hidden="true">&lt;/&gt;</span>
                <span class="text-xl font-bold tracking-tight text-white">Code<span class="text-cyan-300">Quest</span></span>
            </a>

            <div class="flex flex-wrap items-center justify-end gap-x-5 gap-y-2 text-sm">
                <a href="{{ route('home') }}" aria-current="page" class="font-medium text-white transition hover:text-cyan-200">Accueil</a>
                <a href="{{ auth()->check() ? route('student.exercises') : route('register') }}" class="text-slate-300 transition hover:text-white">Explorer les exercices</a>
                <a href="#classement" class="text-slate-300 transition hover:text-white">Classement</a>
                <a href="#comment-ca-marche" class="text-slate-300 transition hover:text-white">Comment ça marche ?</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-white/10 px-4 py-2 font-medium text-slate-200 transition hover:border-violet-400/50 hover:bg-white/5">Mon espace</a>
                @else
                    <a href="{{ route('login') }}" class="font-medium text-slate-300 transition hover:text-white">Se connecter</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-violet-500 px-4 py-2 font-semibold text-white shadow-lg shadow-violet-500/20 transition hover:bg-violet-400 focus-visible:outline-cyan-300">
                        Commencer l’aventure
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main id="contenu">
        <section class="hero-grid relative isolate overflow-hidden">
            <div class="hero-glow pointer-events-none absolute inset-0 -z-10" aria-hidden="true"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 pb-20 pt-16 sm:px-6 sm:pt-24 lg:grid-cols-[1.03fr_0.97fr] lg:px-8 lg:pb-28">
                <div>
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/5 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-300 shadow-[0_0_12px_#22D3EE]" aria-hidden="true"></span>
                        Apprendre en jouant
                    </p>
                    <h1 class="max-w-2xl text-4xl font-black leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Le code devient
                        <span class="hero-title-gradient">ton terrain de jeu.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                        Apprends à programmer, relève des défis, gagne de l’expérience et deviens le développeur que tu rêves d’être.
                    </p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-violet-500 px-5 py-3.5 font-semibold text-white shadow-xl shadow-violet-500/20 transition hover:-translate-y-0.5 hover:bg-violet-400">
                            Commencer gratuitement
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="#missions" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] px-5 py-3.5 font-semibold text-slate-100 transition hover:border-cyan-300/30 hover:bg-white/[0.07]">
                            Découvrir les défis
                            <span class="text-cyan-300" aria-hidden="true">↓</span>
                        </a>
                    </div>
                    <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-400">
                        <span class="inline-flex items-center gap-2"><span class="text-amber-300" aria-hidden="true">✦</span> Progression à ton rythme</span>
                        <span class="inline-flex items-center gap-2"><span class="text-cyan-300" aria-hidden="true">✓</span> Défis corrigés automatiquement</span>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="editor-card rounded-2xl border border-white/10 bg-[#111a2d]/95 p-3 shadow-2xl shadow-violet-950/40 sm:p-4">
                        <div class="flex items-center justify-between border-b border-white/8 px-2 pb-3">
                            <div class="flex items-center gap-2" aria-label="Aperçu de l’éditeur de code">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400/80" aria-hidden="true"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-300/80" aria-hidden="true"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-300/80" aria-hidden="true"></span>
                                <span class="ml-2 text-xs text-slate-400">mission.js</span>
                            </div>
                            <span class="rounded-md border border-cyan-300/15 bg-cyan-300/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-cyan-200">JavaScript</span>
                        </div>
                        <div class="grid grid-cols-[2rem_1fr] gap-x-3 px-2 py-5 font-mono text-xs leading-7 sm:px-4 sm:py-7 sm:text-sm" aria-label="Exemple de code JavaScript">
                            <div class="select-none text-right text-slate-600" aria-hidden="true">01<br>02<br>03<br>04<br>05<br>06</div>
                            <pre class="overflow-x-auto text-slate-200"><code><span class="text-violet-300">function</span> <span class="text-cyan-200">solution</span>(<span class="text-orange-200">nombres</span>) {
  <span class="text-slate-500">// À toi de jouer !</span>
  <span class="text-violet-300">return</span> nombres.<span class="text-cyan-200">reduce</span>(
    (total, nombre) =&gt; total + nombre,
    <span class="text-amber-200">0</span>
  );
}</code></pre>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/5 bg-[#0b1222] px-4 py-3">
                            <div>
                                <p class="text-xs font-medium text-slate-300">{{ $exercises->first()?->title ?? 'Ta première mission t’attend' }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">Aperçu d’une mission CodeQuest</p>
                            </div>
                            <span class="rounded-lg border border-amber-300/20 bg-amber-300/5 px-3 py-1.5 text-xs font-bold text-amber-200">
                                +{{ $exercises->first()?->xp_reward ?? 0 }} XP
                            </span>
                        </div>
                    </div>

                    <div class="motion-float absolute -left-5 top-16 hidden rounded-xl border border-violet-300/20 bg-[#17182f] p-3 shadow-xl sm:block">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Ton niveau</p>
                        <p class="mt-1 text-sm font-bold text-violet-200">{{ $currentLevel?->name ?? 'Prêt à commencer' }}</p>
                    </div>
                    <div class="motion-float-delayed absolute -right-4 -top-5 hidden rounded-xl border border-amber-300/20 bg-[#211d2b] px-4 py-3 shadow-xl sm:block">
                        <p class="text-xs font-semibold text-amber-200"><span aria-hidden="true">✦</span> L’XP t’attend</p>
                        <p class="mt-1 text-[10px] text-slate-400">Chaque réussite compte</p>
                    </div>
                    <div class="absolute -bottom-7 right-5 w-56 rounded-xl border border-white/10 bg-[#111a2d] p-4 shadow-xl sm:right-8">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="text-slate-300">Progression du niveau</span>
                            <span class="font-semibold text-cyan-200">{{ number_format($levelProgress, 0) }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-700/70"
                             role="progressbar"
                             aria-label="Progression vers le prochain niveau"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-valuenow="{{ (int) round($levelProgress) }}">
                            <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-cyan-300 transition-all duration-700"
                                 style="width: {{ $levelProgress }}%"></div>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-500">
                            {{ number_format($currentXp) }} XP
                            @if ($nextLevel)
                                · {{ number_format(max(0, $nextLevel->min_xp - $currentXp)) }} XP avant {{ $nextLevel->name }}
                            @else
                                · Niveau maximal
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section aria-label="Statistiques CodeQuest" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 rounded-2xl border border-white/8 bg-white/[0.025] p-4 sm:grid-cols-4 sm:gap-0 sm:p-6">
                <div class="px-3 py-3 text-center sm:border-r sm:border-white/10">
                    <p class="text-2xl font-black text-white sm:text-3xl">{{ number_format($challengeCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">missions publiées</p>
                </div>
                <div class="px-3 py-3 text-center sm:border-r sm:border-white/10">
                    <p class="text-2xl font-black text-cyan-200 sm:text-3xl">{{ number_format($languageCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">langages proposés</p>
                </div>
                <div class="px-3 py-3 text-center sm:border-r sm:border-white/10">
                    <p class="text-2xl font-black text-violet-200 sm:text-3xl">{{ number_format($levelCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">niveaux à débloquer</p>
                </div>
                <div class="px-3 py-3 text-center">
                    <p class="text-2xl font-black text-amber-200 sm:text-3xl">{{ number_format($studentCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">étudiants inscrits</p>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-slate-600">Données actualisées à partir des missions publiées et des comptes inscrits.</p>
        </section>

        <section id="missions" class="scroll-mt-24 mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">À toi de jouer</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Choisis ta prochaine mission</h2>
                    <p class="mt-3 max-w-2xl text-slate-400">Des défis progressifs pour transformer tes nouvelles compétences en réflexes de développeur.</p>
                </div>
                <a href="{{ auth()->check() ? route('student.exercises') : route('register') }}" class="text-sm font-semibold text-violet-200 transition hover:text-white">
                    Toutes les missions <span aria-hidden="true">→</span>
                </a>
            </div>

            @if ($exercises->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($exercises as $index => $exercise)
                        @php
                            $isUnlocked = ! auth()->check() || $exercise->isUnlockedFor(auth()->user());
                            $difficultyLabel = match ($exercise->difficulty) {
                                'medium' => 'Intermédiaire',
                                'hard' => 'Avancé',
                                'expert' => 'Expert',
                                default => 'Débutant',
                            };
                            $difficultyStyle = match ($exercise->difficulty) {
                                'medium' => 'border-cyan-300/15 bg-cyan-300/5 text-cyan-200',
                                'hard' => 'border-orange-300/15 bg-orange-300/5 text-orange-200',
                                'expert' => 'border-rose-300/15 bg-rose-300/5 text-rose-200',
                                default => 'border-emerald-300/15 bg-emerald-300/5 text-emerald-200',
                            };
                        @endphp
                        <article class="mission-card group flex min-h-full flex-col rounded-2xl border border-white/8 bg-[#111a2d]/70 p-5 transition duration-300 hover:-translate-y-1 hover:border-violet-400/40 hover:bg-[#151e35] hover:shadow-xl hover:shadow-violet-950/30">
                            <div class="mb-5 flex items-center justify-between">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-400/10 font-mono text-sm font-bold text-violet-200" aria-hidden="true">
                                    {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $difficultyStyle }}">{{ $difficultyLabel }}</span>
                            </div>
                            <p class="text-xs font-medium text-slate-500">{{ $exercise->category->name }}</p>
                            <h3 class="mt-2 text-lg font-bold text-white">{{ $exercise->title }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-400">{{ \Illuminate\Support\Str::limit($exercise->description, 105) }}</p>

                            <div class="mt-5 flex items-center justify-between border-t border-white/8 pt-4">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-200">
                                    <span aria-hidden="true">✦</span> +{{ $exercise->xp_reward }} XP
                                </span>
                                @if (auth()->check() && ! $isUnlocked)
                                    <span class="text-right text-[11px] text-slate-500">
                                        @if (auth()->user()->currentLevelNumber() < $exercise->requiredLevelNumber())
                                            Niveau {{ $exercise->requiredLevelNumber() }} requis
                                        @else
                                            Prérequis requis
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300" aria-hidden="true"></span>
                                        {{ auth()->check() ? 'Disponible' : 'À découvrir' }}
                                    </span>
                                @endif
                            </div>
                            @if ($isUnlocked)
                                <a href="{{ auth()->check() ? route('student.exercise-show', $exercise) : route('register') }}"
                                   class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2.5 text-sm font-semibold text-slate-100 transition group-hover:border-violet-400/30 group-hover:bg-violet-500/10">
                                    Commencer la mission <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <span class="mt-4 inline-flex cursor-not-allowed items-center justify-center rounded-lg border border-white/5 bg-white/[0.02] px-3 py-2.5 text-sm font-medium text-slate-500">
                                    @if (auth()->user()->currentLevelNumber() < $exercise->requiredLevelNumber())
                                        Débloque le niveau {{ $exercise->requiredLevelNumber() }}
                                    @else
                                        Termine les prérequis
                                    @endif
                                </span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-12 text-center">
                    <p class="text-lg font-semibold text-white">Les missions arrivent bientôt.</p>
                    <p class="mt-2 text-sm text-slate-400">Aucun exercice n’est actuellement publié.</p>
                </div>
            @endif
        </section>

        <section id="comment-ca-marche" class="scroll-mt-24 border-y border-white/5 bg-[#0e1628]">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto mb-12 max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-violet-300">Ton parcours</p>
                    <h2 class="mt-3 text-3xl font-bold text-white sm:text-4xl">Comment fonctionne CodeQuest ?</h2>
                    <p class="mt-4 text-slate-400">Chaque étape te rapproche de ton prochain niveau.</p>
                </div>
                <ol class="grid gap-4 md:grid-cols-4">
                    @foreach ([
                        ['01', 'Explore', 'Découvre les missions et les langages à ton rythme.', 'text-cyan-200', 'border-cyan-300/20'],
                        ['02', 'Apprends', 'Comprends les concepts en pratiquant sur des exercices ciblés.', 'text-violet-200', 'border-violet-300/20'],
                        ['03', 'Relève les défis', 'Écris du code, lance ta solution et valide les tests.', 'text-amber-200', 'border-amber-300/20'],
                        ['04', 'Progresse', 'Gagne de l’XP, débloque des niveaux et suis ton classement.', 'text-emerald-200', 'border-emerald-300/20'],
                    ] as [$number, $title, $description, $color, $border])
                        <li class="relative rounded-2xl border {{ $border }} bg-[#111a2d] p-5">
                            <span class="font-mono text-xs font-bold {{ $color }}">{{ $number }} / 04</span>
                            <h3 class="mt-5 text-lg font-bold text-white">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $description }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="mb-10 max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Le plaisir de progresser</p>
                <h2 class="mt-3 text-3xl font-bold text-white sm:text-4xl">Chaque effort mérite sa récompense.</h2>
                <p class="mt-4 text-slate-400">Tes réussites deviennent visibles et ta progression reste entre tes mains.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="rounded-2xl border border-amber-300/15 bg-gradient-to-br from-amber-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-amber-200" aria-hidden="true">✦</span>
                    <h3 class="mt-4 font-bold text-white">XP à chaque réussite</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Chaque mission réussie ajoute de l’expérience à ton profil.</p>
                </article>
                <article class="rounded-2xl border border-violet-300/15 bg-gradient-to-br from-violet-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-violet-200" aria-hidden="true">↗</span>
                    <h3 class="mt-4 font-bold text-white">Niveaux à débloquer</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Progresse graduellement et ouvre l’accès à de nouveaux défis.</p>
                </article>
                <article class="rounded-2xl border border-cyan-300/15 bg-gradient-to-br from-cyan-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-cyan-200" aria-hidden="true">◇</span>
                    <h3 class="mt-4 font-bold text-white">Badges de parcours</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Célèbre les étapes franchies et les habitudes que tu construis.</p>
                </article>
                <article class="rounded-2xl border border-blue-300/15 bg-gradient-to-br from-blue-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-blue-200" aria-hidden="true">◎</span>
                    <h3 class="mt-4 font-bold text-white">Classement étudiant</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Compare ta progression et trouve une nouvelle source de motivation.</p>
                </article>
                <article class="rounded-2xl border border-emerald-300/15 bg-gradient-to-br from-emerald-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-emerald-200" aria-hidden="true">⌘</span>
                    <h3 class="mt-4 font-bold text-white">Défis concrets</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Mets tes connaissances en pratique et reçois un retour immédiat.</p>
                </article>
                <article class="rounded-2xl border border-rose-300/15 bg-gradient-to-br from-rose-300/[0.08] to-transparent p-6">
                    <span class="text-2xl text-rose-200" aria-hidden="true">↻</span>
                    <h3 class="mt-4 font-bold text-white">Régularité récompensée</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Reviens apprendre, garde ta série et construis tes compétences durablement.</p>
                </article>
            </div>
        </section>

        <section id="classement" class="scroll-mt-24 border-y border-white/5 bg-[#0e1628]">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-8">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">La communauté</p>
                    <h2 class="mt-3 text-3xl font-bold text-white sm:text-4xl">Le prochain rang peut être le tien.</h2>
                    <p class="mt-4 leading-7 text-slate-400">Découvre les étudiants qui accumulent le plus d’XP et trace ta propre route vers le sommet.</p>
                    <p class="mt-3 text-xs text-slate-500">Aperçu anonymisé du classement général — les points reflètent les données actuelles.</p>
                    <a href="{{ auth()->check() ? route('leaderboard') : route('login') }}"
                       class="mt-7 inline-flex items-center gap-2 rounded-xl border border-cyan-300/20 bg-cyan-300/5 px-5 py-3 font-semibold text-cyan-100 transition hover:bg-cyan-300/10">
                        Voir le classement complet <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse ($leaders as $index => $leader)
                        @php
                            $rank = $index + 1;
                            $rankStyle = match ($rank) {
                                1 => 'border-amber-300/25 bg-amber-300/[0.07] text-amber-200',
                                2 => 'border-slate-300/20 bg-slate-300/[0.05] text-slate-200',
                                default => 'border-orange-300/20 bg-orange-300/[0.05] text-orange-200',
                            };
                            $avatarStyle = match ($rank) {
                                1 => 'bg-amber-300/15 text-amber-200',
                                2 => 'bg-slate-300/10 text-slate-200',
                                default => 'bg-orange-300/10 text-orange-200',
                            };
                        @endphp
                        <article class="flex items-center gap-4 rounded-2xl border px-4 py-4 sm:px-5 {{ $rankStyle }}">
                            <span class="w-8 text-center font-mono text-sm font-black">#{{ $rank }}</span>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full font-bold {{ $avatarStyle }}" aria-hidden="true">C{{ $rank }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-white">Codeur·se {{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $leader->level_name ?? 'Débutant' }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-bold text-amber-200">{{ number_format((int) $leader->total_xp) }} XP</p>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-white/10 bg-[#111a2d] p-7 text-center">
                            <p class="font-semibold text-white">Le classement se construit ici.</p>
                            <p class="mt-2 text-sm text-slate-400">Les étudiants apparaîtront après leurs premières réussites.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-violet-300/15 bg-gradient-to-br from-violet-500/10 via-[#111a2d] to-cyan-400/5 px-6 py-12 sm:px-12 sm:py-16">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200">Prêt·e pour la première mission ?</p>
                <h2 class="mx-auto mt-4 max-w-2xl text-3xl font-black text-white sm:text-4xl">Ton prochain niveau commence par une ligne de code.</h2>
                <p class="mx-auto mt-4 max-w-xl text-slate-300">Crée ton compte gratuitement et avance à ton rythme, une mission après l’autre.</p>
                <a href="{{ auth()->check() ? route('student.exercises') : route('register') }}"
                   class="mt-8 inline-flex items-center gap-2 rounded-xl bg-violet-500 px-6 py-3.5 font-semibold text-white shadow-xl shadow-violet-500/20 transition hover:-translate-y-0.5 hover:bg-violet-400">
                    Commencer l’aventure <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/5">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-7 text-sm text-slate-500 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="font-semibold text-slate-300">Code<span class="text-cyan-300">Quest</span></a>
            <p>Apprendre à coder, une mission à la fois.</p>
            <a href="{{ route('login') }}" class="transition hover:text-white">Se connecter</a>
        </div>
    </footer>
</body>
</html>
