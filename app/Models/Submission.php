<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    protected $fillable = [
        'user_id',
        'exercise_id',
        'source_code',
        'status',
        'result',
        'execution_id',
        'execution_time_ms',
        'passed_tests',
        'total_tests',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'execution_time_ms' => 'integer',
            'passed_tests' => 'integer',
            'total_tests' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Étudiant ayant effectué la soumission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Exercice concerné par la soumission.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Validation de réussite associée à cette soumission.
     */
    public function completion(): HasOne
    {
        return $this->hasOne(ExerciseCompletion::class);
    }

    /**
     * Transaction XP associée à cette soumission.
     */
    public function xpTransaction(): HasOne
    {
        return $this->hasOne(XpTransaction::class);
    }
}

