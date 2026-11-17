<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseTestCase extends Model
{
    protected $fillable = [
        'exercise_id',
        'input_data',
        'expected_output',
        'is_hidden',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'input_data' => 'array',
            'expected_output' => 'array',
            'is_hidden' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Exercice auquel appartient ce cas de test.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
