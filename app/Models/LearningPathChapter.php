<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class LearningPathChapter extends Model
{
    protected $fillable = [
        'learning_path_id',
        'title',
        'slug',
        'description',
        'course_content',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class)->orderBy('sort_order')->orderBy('id');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(ChapterQuizQuestion::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isCompletedBy(User $user): bool
    {
        $questionCount = $this->quizQuestions()->count();

        return $questionCount > 0
            && $this->quizQuestions()
                ->whereDoesntHave('completions', fn (Builder $query) => $query
                    ->where('user_id', $user->id))
                ->doesntExist();
    }

    public function isAccessibleFor(User $user): bool
    {
        $precedingChapters = self::query()
            ->where('learning_path_id', $this->learning_path_id)
            ->whereHas('quizQuestions')
            ->where(function ($query) {
                $query->where('sort_order', '<', $this->sort_order)
                    ->orWhere(function ($sameOrder) {
                        $sameOrder->where('sort_order', $this->sort_order)
                            ->where('id', '<', $this->id);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($precedingChapters as $chapter) {
            if (! $chapter->isCompletedBy($user)) {
                return false;
            }
        }

        return true;
    }
}
