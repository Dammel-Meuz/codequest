<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_google_and_github(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('social.redirect', 'google'))
            ->assertSee(route('social.redirect', 'github'));
    }

    public function test_google_redirect_starts_the_socialite_flow(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/oauth'));
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('social.redirect', 'google'))
            ->assertRedirect('https://accounts.google.com/oauth');
    }

    public function test_invalid_oauth_state_returns_a_helpful_error_without_disabling_state_validation(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_google_can_register_a_verified_email_and_sign_in(): void
    {
        $this->mockSocialProvider('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Awa Diallo',
            'email' => 'AWA@example.com',
            'email_verified' => true,
        ]));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'awa@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);
    }

    public function test_google_does_not_create_an_account_for_an_unverified_email(): void
    {
        $this->mockSocialProvider('google', SocialiteUser::fake([
            'id' => 'google-unverified-123',
            'email' => 'unverified@example.com',
            'email_verified' => false,
        ]));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_verified_social_email_links_to_existing_account_without_changing_its_role(): void
    {
        $admin = User::factory()->create([
            'email' => 'existing@example.com',
            'role' => 'admin',
        ]);

        $this->mockSocialProvider('google', SocialiteUser::fake([
            'id' => 'google-existing-123',
            'name' => 'Existing Admin',
            'email' => 'EXISTING@example.com',
            'email_verified' => true,
        ]));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $admin->id,
            'provider' => 'google',
            'provider_id' => 'google-existing-123',
        ]);
    }

    public function test_github_can_sign_in_with_its_primary_verified_email(): void
    {
        $this->mockSocialProvider('github', SocialiteUser::fake([
            'id' => 'github-user-456',
            'name' => 'Moussa Ndiaye',
            'email' => 'moussa@example.com',
        ]));

        $this->get(route('social.callback', 'github'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'github',
            'provider_id' => 'github-user-456',
        ]);
    }

    public function test_github_does_not_create_an_account_without_a_verified_email(): void
    {
        $this->mockSocialProvider('github', SocialiteUser::fake([
            'id' => 'github-private-email-456',
            'email' => null,
        ]));

        $this->get(route('social.callback', 'github'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_linked_social_account_can_sign_in_again(): void
    {
        $user = User::factory()->create();
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_id' => 'google-linked-789',
        ]);

        $this->mockSocialProvider('google', SocialiteUser::fake([
            'id' => 'google-linked-789',
            'email' => null,
            'email_verified' => false,
        ]));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    private function mockSocialProvider(string $providerName, SocialiteUser $socialUser): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with($providerName)
            ->andReturn($provider);
    }
}
