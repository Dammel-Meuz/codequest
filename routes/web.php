
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Page d'accueil
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Inscription
Route::livewire('/register', 'auth.register')
    ->middleware('guest')
    ->name('register');

// Connexion
Route::livewire('/login', 'auth.login')
    ->middleware('guest')
    ->name('login');

// Déconnexion
Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Tableau de bord
Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

// Profil utilisateur
Route::view('/profile', 'profile')
    ->middleware('auth')
    ->name('profile');

// Espace réservé aux administrateurs
Route::livewire('/admin', 'admin.dashboard')
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');
// Gestion des catégories
Route::livewire('/admin/categories', 'admin.categories')
    ->middleware(['auth', 'admin'])
    ->name('admin.categories');
// Gestion des exercices
Route::livewire('/admin/exercises', 'admin.exercises')
    ->middleware(['auth', 'admin'])
    ->name('admin.exercises');
// Gestion des utilisateurs
 Route::livewire('/admin/users', 'admin.users')
    ->middleware(['auth', 'admin'])
    ->name('admin.users');
// Gestion des soumissions
Route::livewire('/exercises', 'student.exercises')
    ->middleware('auth')
    ->name('student.exercises');
// Affichage d'un exercice spécifique
Route::livewire('/exercises/{exercise}', 'student.exercise-show')
    ->middleware('auth')
    ->name('student.exercise-show');