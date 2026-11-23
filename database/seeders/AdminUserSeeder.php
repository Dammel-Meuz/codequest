<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'ndoyemamemoussa@gmail.com']);

        if (! $admin->exists) {
            $admin->name = 'Mame Moussa NDOYE';
            $admin->password = Str::random(64);
        }

        $admin->role = 'admin';
        $admin->email_verified_at ??= now();
        $admin->save();
    }
}
