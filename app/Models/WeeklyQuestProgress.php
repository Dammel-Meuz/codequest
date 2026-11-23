<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyQuestProgress extends Model
{
    protected $table = 'weekly_quest_progresses';

    protected $fillable = [
        'weekly_quest_id',
        'user_id',
        'progress',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function weeklyQuest(): BelongsTo
    {
        return $this->belongsTo(WeeklyQuest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
