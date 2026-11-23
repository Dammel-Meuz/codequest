<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            Log::warning('Social authentication state validation failed.', [
                'provider' => $provider,
            ]);

            return redirect()->route('login')->with(
                'error',
                'La connexion avec '.ucfirst($provider).' a expiré ou la session a été perdue. Vérifie que le domaine et le protocole HTTPS restent identiques, puis réessaie.'
            );
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($account) {
            $user = $account->user;
        } else {
            $email = Str::lower(trim((string) $socialUser->getEmail()));

            if (! $this->hasVerifiedEmail($provider, $socialUser)
                || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 255) {
                return redirect()->route('login')->with(
                    'error',
                    'Le fournisseur doit transmettre une adresse e-mail valide et vérifiée pour créer ou relier ton compte.'
                );
            }

            $user = DB::transaction(function () use ($provider, $socialUser, $email): ?User {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first();

                if ($user) {
                    $existingAccount = SocialAccount::query()
                        ->where('user_id', $user->id)
                        ->where('provider', $provider)
                        ->exists();

                    if ($existingAccount) {
                        return null;
                    }

                    if (! $user->hasVerifiedEmail()) {
                        $user->forceFill(['email_verified_at' => now()])->save();
                    }
                } else {
                    $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname()));

                    $user = User::create([
                        'name' => Str::limit($name !== '' ? $name : Str::before($email, '@'), 255, ''),
                        'email' => $email,
                        'password' => Str::random(64),
                    ]);
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => (string) $socialUser->getId(),
                ]);

                return $user;
            });

            if (! $user) {
                return redirect()->route('login')->with(
                    'error',
                    'Un autre compte de ce fournisseur est déjà lié à ton adresse e-mail.'
                );
            }
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function hasVerifiedEmail(string $provider, SocialiteUser $socialUser): bool
    {
        if ($provider === 'google') {
            return ($socialUser->user['email_verified'] ?? false) === true;
        }

        // Socialite's GitHub provider selects only the primary, verified address.
        return $provider === 'github' && filled($socialUser->getEmail());
    }
}
