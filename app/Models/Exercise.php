<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'category_id',
        'learning_path_chapter_id',
        'title',
        'slug',
        'description',
        'hints',
        'success_explanation',
        'difficulty',
        'xp_reward',
        'starter_code',
        'language',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'xp_reward' => 'integer',
            'hints' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Catégorie de cet exercice.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(LearningPathChapter::class, 'learning_path_chapter_id');
    }

    /**
     * Cas de test de cet exercice.
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(ExerciseTestCase::class);
    }

    /**
     * Soumissions des étudiants pour cet exercice.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Validations de réussite de cet exercice.
     */
    public function completions(): HasMany
    {
        return $this->hasMany(ExerciseCompletion::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'exercise_prerequisites',
            'exercise_id',
            'prerequisite_exercise_id'
        );
    }

    public function requiredFor(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'exercise_prerequisites',
            'prerequisite_exercise_id',
            'exercise_id'
        );
    }

    public function scopeAvailableFor(Builder $query, User $user): Builder
    {
        $unlockedDifficulties = collect([
            'easy' => 1,
            'medium' => 2,
            'hard' => 4,
            'expert' => 6,
        ])->filter(fn (int $requiredLevel) => $requiredLevel <= $user->currentLevelNumber())
            ->keys()
            ->all();

        return $query
            ->whereIn('difficulty', $unlockedDifficulties)
            ->whereDoesntHave('prerequisites', function (Builder $prerequisites) use ($user) {
                $prerequisites->whereDoesntHave('completions', fn (Builder $completions) => $completions
                    ->where('user_id', $user->id));
            });
    }

    public function requiredLevelNumber(): int
    {
        return match ($this->difficulty) {
            'medium' => 2,
            'hard' => 4,
            'expert' => 6,
            default => 1,
        };
    }

    public function isUnlockedFor(User $user): bool
    {
        if ($user->exerciseCompletions()->where('exercise_id', $this->id)->exists()) {
            return true;
        }

        if ($user->currentLevelNumber() < $this->requiredLevelNumber()) {
            return false;
        }

        $prerequisiteIds = $this->prerequisites()->pluck('exercises.id');

        return $prerequisiteIds->isEmpty()
            || $user->exerciseCompletions()
                ->whereIn('exercise_id', $prerequisiteIds)
                ->count() === $prerequisiteIds->count();
    }
}

