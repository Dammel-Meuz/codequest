<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_admin_and_preserves_the_password_when_reseeded(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('email', 'ndoyemamemoussa@gmail.com')->firstOrFail();
        $passwordHash = $admin->password;

        $this->assertSame('admin', $admin->role);
        $this->assertNotNull($admin->email_verified_at);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame($passwordHash, $admin->fresh()->password);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_promotes_an_existing_account_without_replacing_its_password(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'ndoyemamemoussa@gmail.com',
            'role' => 'student',
        ]);
        $passwordHash = $existingUser->password;

        $this->seed(AdminUserSeeder::class);

        $this->assertSame('admin', $existingUser->fresh()->role);
        $this->assertSame($passwordHash, $existingUser->fresh()->password);
        $this->assertNotNull($existingUser->fresh()->email_verified_at);
    }
}
