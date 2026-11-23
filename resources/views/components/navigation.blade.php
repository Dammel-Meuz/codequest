<header class="border-b border-slate-800 bg-slate-950/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-indigo-300" aria-label="CodeQuest, tableau de bord">
            CodeQuest
        </a>

        <button type="button"
                class="inline-flex items-center justify-center rounded-lg border border-slate-700 p-2 text-slate-200 transition hover:border-slate-500 hover:bg-slate-800 focus-visible:outline-offset-4 lg:hidden"
                data-mobile-menu-toggle
                aria-controls="primary-navigation"
                aria-expanded="false"
                aria-label="Ouvrir le menu">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <button type="button"
                class="invisible pointer-events-none fixed inset-0 z-40 bg-slate-950/70 opacity-0 transition-opacity duration-300 lg:hidden"
                data-mobile-menu-backdrop
                aria-label="Fermer le menu"
                tabindex="-1"></button>

        <nav id="primary-navigation"
             aria-label="Navigation principale"
             class="invisible fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-slate-800 bg-slate-950 px-5 py-5 text-sm shadow-2xl transition-transform duration-300 lg:visible lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:flex-row lg:items-center lg:gap-x-5 lg:gap-y-2 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0 lg:shadow-none">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4 lg:hidden">
                <span class="text-lg font-semibold text-white">Menu</span>
                <button type="button"
                        class="rounded-lg border border-slate-700 p-2 text-slate-200 transition hover:border-slate-500 hover:bg-slate-800"
                        data-mobile-menu-close
                        aria-label="Fermer le menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-x-5 lg:gap-y-2">
                <a href="{{ route('dashboard') }}"
                   @class([
                       'text-slate-300 transition hover:text-white',
                       'font-semibold text-white' => request()->routeIs('dashboard'),
                   ])
                   @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    Tableau de bord
                </a>
                <a href="{{ route('student.exercises') }}"
                   @class([
                       'text-slate-300 transition hover:text-white',
                       'font-semibold text-white' => request()->routeIs('student.exercises', 'student.exercise-show'),
                   ])
                   @if (request()->routeIs('student.exercises', 'student.exercise-show')) aria-current="page" @endif>
                    Exercices
                </a>
                <a href="{{ route('student.learning') }}"
                   @class([
                       'text-slate-300 transition hover:text-white',
                       'font-semibold text-white' => request()->routeIs('student.learning', 'student.chapter-course'),
                   ])
                   @if (request()->routeIs('student.learning', 'student.chapter-course')) aria-current="page" @endif>
                    Apprentissage
                </a>
                <a href="{{ route('leaderboard') }}"
                   @class([
                       'text-slate-300 transition hover:text-white',
                       'font-semibold text-white' => request()->routeIs('leaderboard'),
                   ])
                   @if (request()->routeIs('leaderboard')) aria-current="page" @endif>
                    Classement
                </a>
                <a href="{{ route('profile') }}"
                   @class([
                       'text-slate-300 transition hover:text-white',
                       'font-semibold text-white' => request()->routeIs('profile'),
                   ])
                   @if (request()->routeIs('profile')) aria-current="page" @endif>
                    Mon profil
                </a>

                @if (auth()->user()?->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="text-slate-300 transition hover:text-white">
                        Administration
                    </a>
                    <a href="{{ route('admin.learning-paths') }}"
                       @class([
                           'text-slate-300 transition hover:text-white',
                           'font-semibold text-white' => request()->routeIs('admin.learning-paths'),
                       ])
                       @if (request()->routeIs('admin.learning-paths')) aria-current="page" @endif>
                        Parcours
                    </a>
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-auto lg:mt-0">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg border border-slate-700 px-3 py-2 text-left text-slate-200 transition hover:border-slate-500 hover:bg-slate-800 lg:w-auto lg:text-center">
                    Déconnexion
                </button>
            </form>
        </nav>
    </div>
</header>
