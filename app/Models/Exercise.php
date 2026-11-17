<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
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
}

