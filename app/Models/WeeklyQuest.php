<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyQuest extends Model
{
    protected $fillable = [
        'week_start',
        'title',
        'description',
        'goal_count',
        'reward_xp',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'goal_count' => 'integer',
            'reward_xp' => 'integer',
        ];
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(WeeklyQuestProgress::class);
    }
}
