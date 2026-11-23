<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'submission_id',
        'chapter_quiz_completion_id',
        'weekly_quest_progress_id',
        'amount',
        'reason',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * Étudiant concerné par la transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Soumission associée à la transaction.
     * Cette relation est facultative.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function chapterQuizCompletion(): BelongsTo
    {
        return $this->belongsTo(ChapterQuizCompletion::class);
    }

    public function weeklyQuestProgress(): BelongsTo
    {
        return $this->belongsTo(WeeklyQuestProgress::class);
    }
}

