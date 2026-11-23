<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChapterQuizCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'chapter_quiz_question_id',
        'selected_option',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ChapterQuizQuestion::class, 'chapter_quiz_question_id');
    }

    public function xpTransaction(): HasOne
    {
        return $this->hasOne(XpTransaction::class);
    }
}
