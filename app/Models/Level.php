<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = [
        'level_number',
        'name',
        'min_xp',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'min_xp' => 'integer',
        ];
    }
}

