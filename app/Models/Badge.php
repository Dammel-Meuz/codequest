<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'criteria_type',
        'criteria_value',
    ];

    protected function casts(): array
    {
        return [
            'criteria_value' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('awarded_at');
    }
}
