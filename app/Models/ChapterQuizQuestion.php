<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChapterQuizQuestion extends Model
{
    protected $fillable = [
        'learning_path_chapter_id',
        'prompt',
        'options',
        'correct_option',
        'explanation',
        'xp_reward',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'xp_reward' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(LearningPathChapter::class, 'learning_path_chapter_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChapterQuizCompletion::class);
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->completions()->where('user_id', $user->id)->exists();
    }
}
