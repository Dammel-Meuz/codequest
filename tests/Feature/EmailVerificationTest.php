<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_verification_notification_and_shows_the_notice(): void
    {
        Notification::fake();

        Livewire::test('auth.register')
            ->set('name', 'Amina Diallo')
            ->set('email', 'amina@example.com')
            ->set('password', 'StrongPassword123')
            ->set('password_confirmation', 'StrongPassword123')
            ->call('register')
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'amina@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Vérifie ton adresse e-mail')
            ->assertSee(route('verification.send'));
    }

    public function test_unverified_users_are_redirected_and_can_request_another_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_can_log_in_but_is_sent_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        Livewire::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_valid_signed_link_verifies_the_email_and_unlocks_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard').'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->get(route('dashboard'))->assertOk();
    }
}
