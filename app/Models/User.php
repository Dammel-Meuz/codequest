<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected $attributes = [
        'total_xp' => 0,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'total_xp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'total_xp' => 'integer',
        ];
    }

    /**
     * Soumissions de l'utilisateur.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Exercices terminés par l'utilisateur.
     */
    public function exerciseCompletions(): HasMany
    {
        return $this->hasMany(ExerciseCompletion::class);
    }

    public function exerciseDrafts(): HasMany
    {
        return $this->hasMany(StudentExerciseDraft::class);
    }

    public function chapterQuizCompletions(): HasMany
    {
        return $this->hasMany(ChapterQuizCompletion::class);
    }

    public function weeklyQuestProgresses(): HasMany
    {
        return $this->hasMany(WeeklyQuestProgress::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Historique des transactions XP.
     */
    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('awarded_at');
    }

    public function learningStreak(): HasOne
    {
        return $this->hasOne(LearningStreak::class);
    }

    public function currentLevelNumber(): int
    {
        return $this->currentLevel()?->level_number ?? 1;
    }

    /**
     * Récupère le niveau actuel de l'utilisateur.
     */
    public function currentLevel(): ?Level
    {
        return Level::where('min_xp', '<=', $this->total_xp)
            ->orderByDesc('min_xp')
            ->first();
    }

    /**
     * Récupère le prochain niveau à atteindre.
     */
    public function nextLevel(): ?Level
    {
        return Level::where('min_xp', '>', $this->total_xp)
            ->orderBy('min_xp')
            ->first();
    }
}
