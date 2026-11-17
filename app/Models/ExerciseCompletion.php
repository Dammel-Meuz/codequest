<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'exercise_id',
        'submission_id',
        'xp_earned',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'xp_earned' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Étudiant ayant réussi l'exercice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Exercice terminé.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Soumission ayant validé la réussite.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
