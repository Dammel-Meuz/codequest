
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\Exercise;
use App\Models\Level;
use App\Models\User;

// Page d'accueil
Route::get('/', function () {
    $user = request()->user();
    $currentLevel = $user?->currentLevel()
        ?? Level::query()->where('min_xp', '<=', (int) ($user?->total_xp ?? 0))
            ->orderByDesc('min_xp')
            ->first();
    $nextLevel = $user
        ? $user->nextLevel()
        : Level::query()
            ->where('min_xp', '>', $currentLevel?->min_xp ?? -1)
            ->orderBy('min_xp')
            ->first();
    $currentXp = (int) ($user?->total_xp ?? 0);

    if ($nextLevel && $currentLevel) {
        $levelRange = $nextLevel->min_xp - $currentLevel->min_xp;
        $levelProgress = $levelRange > 0
            ? min(100, max(0, (($currentXp - $currentLevel->min_xp) / $levelRange) * 100))
            : 0;
    } else {
        $levelProgress = $currentLevel ? 100 : 0;
    }

    return view('welcome', [
        'exercises' => Exercise::query()
            ->with('category')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get(),
        'challengeCount' => Exercise::query()->where('is_published', true)->count(),
        'languageCount' => Exercise::query()
            ->where('is_published', true)
            ->distinct()
            ->count('language'),
        'levelCount' => Level::query()->count(),
        'studentCount' => User::query()->where('role', 'student')->count(),
        'leaders' => User::query()
            ->where('role', 'student')
            ->select('users.id', 'users.name', 'users.total_xp')
            ->selectSub(
                Level::query()
                    ->select('name')
                    ->whereColumn('levels.min_xp', '<=', 'users.total_xp')
                    ->orderByDesc('levels.min_xp')
                    ->limit(1),
                'level_name'
            )
            ->orderByDesc('total_xp')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(3)
            ->get(),
        'currentXp' => $currentXp,
        'currentLevel' => $currentLevel,
        'nextLevel' => $nextLevel,
        'levelProgress' => $levelProgress,
    ]);
})->name('home');

// Inscription
Route::livewire('/register', 'auth.register')
    ->middleware('guest')
    ->name('register');

// Connexion
Route::livewire('/login', 'auth.login')
    ->middleware('guest')
    ->name('login');

Route::middleware(['guest', 'throttle:10,1'])
    ->prefix('/auth/{provider}')
    ->whereIn('provider', ['google', 'github'])
    ->group(function () {
        Route::get('/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
    });

Route::livewire('/forgot-password', 'auth.forgot-password')
    ->middleware('guest')
    ->name('password.request');

Route::livewire('/reset-password/{token}', 'auth.reset-password')
    ->middleware('guest')
    ->name('password.reset');

// Déconnexion
Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::get('/verify-email', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('dashboard');
    }

    return response()->view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $request) {
    if (! $request->user()->hasVerifiedEmail()) {
        $request->fulfill();
    }

    return redirect()->intended(route('dashboard').'?verified=1');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->intended(route('dashboard'));
    }

    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::put('/password', function (Request $request) {
    $validated = $request->validate([
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
    ], [
        'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        'password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
        'password.min' => 'Le nouveau mot de passe doit contenir au moins :min caractères.',
    ]);

    $request->user()->update(['password' => $validated['password']]);

    return back()->with('status', 'password-updated');
})->middleware(['auth', 'verified', 'throttle:6,1'])->name('password.update');

// Tableau de bord
Route::get('/dashboard', StudentDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Profil utilisateur
Route::view('/profile', 'profile')
    ->middleware(['auth', 'verified'])
    ->name('profile');

Route::livewire('/leaderboard', 'student.leaderboard')
    ->middleware(['auth', 'verified'])
    ->name('leaderboard');

Route::livewire('/students/{user}', 'student.public-profile')
    ->middleware(['auth', 'verified'])
    ->name('student.public-profile');

// Espace réservé aux administrateurs
Route::livewire('/admin', 'admin.dashboard')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');
// Gestion des catégories
Route::livewire('/admin/categories', 'admin.categories')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.categories');
Route::livewire('/admin/learning-paths', 'admin.learning-paths')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.learning-paths');
Route::livewire('/admin/chapters/{chapter}/course', 'admin.chapter-course')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.chapter-course');
Route::livewire('/chapters/{chapter}/course', 'student.chapter-course')
    ->middleware(['auth', 'verified'])
    ->name('student.chapter-course');
// Gestion des exercices
Route::livewire('/admin/exercises', 'admin.exercises')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.exercises');
// Gestion des utilisateurs
 Route::livewire('/admin/users', 'admin.users')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.users');
// Gestion des soumissions
Route::livewire('/exercises', 'student.exercises')
    ->middleware(['auth', 'verified'])
    ->name('student.exercises');
Route::livewire('/learning', 'student.learning')
    ->middleware(['auth', 'verified'])
    ->name('student.learning');
// Affichage d'un exercice spécifique
Route::livewire('/exercises/{exercise}', 'student.exercise-show')
    ->middleware(['auth', 'verified'])
    ->name('student.exercise-show');