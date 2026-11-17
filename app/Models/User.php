<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * Historique des transactions XP.
     */
    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
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
