
<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function toggleRole(int $id)
    {
        $user = User::findOrFail($id);

        // Empêcher l'administrateur de modifier son propre rôle.
        if ($user->id === Auth::id()) {
            session()->flash('error', 'Tu ne peux pas modifier ton propre rôle.');
            return;
        }

        // Empêcher la rétrogradation du dernier administrateur.
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();

            if ($adminCount <= 1) {
                session()->flash('error', 'Impossible de rétrograder le dernier administrateur.');
                return;
            }

            $user->update(['role' => 'student']);

            session()->flash('success', 'L’administrateur est maintenant étudiant.');
            return;
        }

        $user->update(['role' => 'admin']);

        session()->flash('success', 'L’étudiant est maintenant administrateur.');
    }

    public function render()
    {
        $users = User::query()
            ->withCount('exerciseCompletions')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('components.admin.⚡users', [
            'users' => $users,
            'totalUsers' => User::count(),
            'totalStudents' => User::where('role', 'student')->count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
        ])->layout('layouts.app');
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white p-6">
    <div class="max-w-7xl mx-auto space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Gestion des utilisateurs</h1>
                <p class="text-slate-400 mt-2">
                    Consulte et gère les comptes des membres de CodeQuest.
                </p>
            </div>

            <a href="{{ route('admin.dashboard') }}"
               class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700">
                ← Retour au tableau de bord
            </a>
        </div>

        @if (session()->has('success'))
            <div class="rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 p-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 p-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <p class="text-slate-400 text-sm">Total utilisateurs</p>
                <p class="text-3xl font-bold mt-2">{{ $totalUsers }}</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <p class="text-slate-400 text-sm">Étudiants</p>
                <p class="text-3xl font-bold text-blue-400 mt-2">{{ $totalStudents }}</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <p class="text-slate-400 text-sm">Administrateurs</p>
                <p class="text-3xl font-bold text-purple-400 mt-2">{{ $totalAdmins }}</p>
            </div>

        </div>

        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h2 class="text-xl font-semibold">Liste des utilisateurs</h2>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher par nom ou e-mail..."
                    class="w-full sm:max-w-sm bg-slate-800 border border-slate-700 rounded-lg p-3 text-white placeholder-slate-500"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="p-3">Utilisateur</th>
                            <th class="p-3">E-mail</th>
                            <th class="p-3">XP</th>
                            <th class="p-3">Exercices terminés</th>
                            <th class="p-3">Rôle</th>
                            <th class="p-3">Inscription</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800">
                        @forelse ($users as $user)
                            <tr>
                                <td class="p-3 font-medium">
                                    {{ $user->name }}

                                    @if ($user->id === auth()->id())
                                        <span class="block text-xs text-indigo-400">Ton compte</span>
                                    @endif
                                </td>

                                <td class="p-3 text-slate-300">
                                    {{ $user->email }}
                                </td>

                                <td class="p-3 font-semibold text-yellow-400">
                                    {{ number_format($user->total_xp) }} XP
                                </td>

                                <td class="p-3">
                                    {{ $user->exercise_completions_count }}
                                </td>

                                <td class="p-3">
                                    @if ($user->role === 'admin')
                                        <span class="px-3 py-1 rounded-full bg-purple-500/10 text-purple-400 text-sm">
                                            Administrateur
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 text-sm">
                                            Étudiant
                                        </span>
                                    @endif
                                </td>

                                <td class="p-3 text-slate-400 text-sm whitespace-nowrap">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>

                                <td class="p-3">
                                    @if ($user->id !== auth()->id())
                                        <button
                                            wire:click="toggleRole({{ $user->id }})"
                                            wire:confirm="Confirmer le changement de rôle de cet utilisateur ?"
                                            class="text-sm font-medium {{ $user->role === 'admin' ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }}"
                                        >
                                            {{ $user->role === 'admin' ? 'Rétrograder' : 'Promouvoir admin' }}
                                        </button>
                                    @else
                                        <span class="text-slate-500 text-sm">Non modifiable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400">
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>

        </section>

    </div>
</div>

