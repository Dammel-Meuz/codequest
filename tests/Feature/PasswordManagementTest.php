<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_branded_email_without_exposing_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Mot de passe oublié');

        Livewire::test('auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee('Si un compte correspond à cette adresse');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $notification = Notification::sent($user, ResetPasswordNotification::class)->first();
        $mail = $notification->toMail($user);
        parse_str((string) parse_url($mail->viewData['actionUrl'], PHP_URL_QUERY), $query);

        $this->assertSame('emails.auth.action', $mail->view);
        $this->assertSame('Réinitialise ton mot de passe CodeQuest', $mail->subject);
        $this->assertSame($user->email, $query['email'] ?? null);
        $this->assertStringContainsString('CodeQuest', $mail->render());
        $this->assertStringContainsString('background-color:#8b5cf6', $mail->render());

        Livewire::test('auth.forgot-password')
            ->set('email', 'inconnu@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee('Si un compte correspond à cette adresse');
    }

    public function test_password_reset_link_changes_the_password_and_can_only_be_used_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Choisir un nouveau mot de passe');

        Livewire::withQueryParams(['email' => $user->email])
            ->test('auth.reset-password', ['token' => $token])
            ->assertSee('Choisir un nouveau mot de passe')
            ->set('password', 'NewPassword456')
            ->set('password_confirmation', 'NewPassword456')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user, $token));

        Livewire::withQueryParams(['email' => $user->email])
            ->test('auth.reset-password', ['token' => $token])
            ->set('password', 'AnotherPassword789')
            ->set('password_confirmation', 'AnotherPassword789')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_authenticated_user_can_change_password_only_with_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPassword123']);

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('password.update'), [
                'current_password' => 'incorrect',
                'password' => 'UpdatedPassword456',
                'password_confirmation' => 'UpdatedPassword456',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('CurrentPassword123', $user->fresh()->password));

        $this->put(route('password.update'), [
            'current_password' => 'CurrentPassword123',
            'password' => 'UpdatedPassword456',
            'password_confirmation' => 'UpdatedPassword456',
        ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('UpdatedPassword456', $user->fresh()->password));
        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('Changer le mot de passe')
            ->assertSee('Ton mot de passe a été changé avec succès.')
            ->assertDontSee('password-updated');
    }

    public function test_verification_email_uses_the_codequest_brand_template(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();
        $notification = Notification::sent($user, VerifyEmail::class)->first();
        $mail = $notification->toMail($user);

        $this->assertSame('emails.auth.action', $mail->view);
        $this->assertSame('Vérifie ton adresse e-mail', $mail->subject);
        $this->assertSame('Vérifier mon adresse e-mail', $mail->viewData['actionLabel']);
        $this->assertStringContainsString('background-color:#8b5cf6', $mail->render());
    }
}
