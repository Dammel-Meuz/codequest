<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\Submission;


new class extends Component
{
      public function render()
    {
        return view("components.admin.⚡dashboard", [
            'totalStudents' => User::where('role', 'student')->count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalExercises' => Exercise::count(),
            'totalCategories' => Category::count(),
            'totalSubmissions' => Submission::count(),
            'recentUsers' => User::latest()->take(5)->get(),
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 px-6 py-10 text-white">

    <div class="mx-auto max-w-7xl">

        <div class="mb-10">
            <h1 class="text-3xl font-bold text-blue-400">
                Administration CodeQuest
            </h1>

            <p class="mt-2 text-slate-400">
                Vue générale de la plateforme.
            </p>
        </div>

        <!-- Statistiques -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Étudiants</p>
                <p class="mt-3 text-3xl font-bold text-blue-400">
                    {{ $totalStudents }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Administrateurs</p>
                <p class="mt-3 text-3xl font-bold text-purple-400">
                    {{ $totalAdmins }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Exercices</p>
                <p class="mt-3 text-3xl font-bold text-green-400">
                    {{ $totalExercises }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Catégories</p>
                <p class="mt-3 text-3xl font-bold text-yellow-400">
                    {{ $totalCategories }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <p class="text-slate-400">Soumissions</p>
                <p class="mt-3 text-3xl font-bold text-pink-400">
                    {{ $totalSubmissions }}
                </p>
            </div>

        </div>

        <!-- Derniers utilisateurs -->
        <div class="mt-10 rounded-xl border border-slate-800 bg-slate-900 p-6">

            <h2 class="mb-6 text-xl font-bold">
                Derniers utilisateurs inscrits
            </h2>

            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="border-b border-slate-700 text-sm text-slate-400">
                        <tr>
                            <th class="p-3">Nom</th>
                            <th class="p-3">E-mail</th>
                            <th class="p-3">Rôle</th>
                            <th class="p-3">Inscription</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr class="border-b border-slate-800">
                                <td class="p-3">{{ $user->name }}</td>
                                <td class="p-3">{{ $user->email }}</td>
                                <td class="p-3">
                                    {{ ucfirst($user->role) }}
                                </td>
                                <td class="p-3">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-3 text-center text-slate-400">
                                    Aucun utilisateur enregistré.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>
        </div>

        <!-- Navigation administration -->
        <div class="mt-10">
            <h2 class="mb-5 text-xl font-bold">
                Gestion de la plateforme
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">

               <a href="{{ route('admin.categories') }}"
                    class="block rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:border-blue-500 hover:bg-slate-800">

                        <h3 class="font-semibold text-blue-400">
                            Catégories
                        </h3>

                        <p class="mt-2 text-sm text-slate-400">
                            Gérer les catégories de programmation.
                        </p>

                        <p class="mt-4 text-sm font-semibold text-blue-400">
                            Accéder à la gestion →
                        </p>
                    </a>

                
                    <a href="{{ route('admin.exercises') }}"
                    class="block bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500 transition">
                        <h3 class="text-lg font-semibold text-white">Exercices</h3>
                        <p class="text-slate-400 text-sm mt-2">
                            Créer, modifier et gérer les exercices.
                        </p>
                        <span class="inline-block mt-4 text-indigo-400">
                            Accéder à la gestion →
                        </span>
                    </a>


                
                <a href="{{ route('admin.users') }}"
                class="block bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500 transition">
                    <h3 class="text-lg font-semibold text-white">Utilisateurs</h3>
                    <p class="text-slate-400 text-sm mt-2">
                        Consulter les comptes, les XP et gérer les rôles.
                    </p>
                    <span class="inline-block mt-4 text-indigo-400">
                        Accéder à la gestion →
                    </span>
                </a>
                <a href="{{ route('student.exercises') }}"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">
                    Explorer les exercices
                </a>


            </div>
        </div>

        <div class="mt-8">
            <a href="{{ route('dashboard') }}"
               class="text-blue-400 hover:underline">
                ← Retour au tableau de bord
            </a>
        </div>

    </div>

</div>