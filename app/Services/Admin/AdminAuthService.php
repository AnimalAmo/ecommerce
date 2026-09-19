<?php

namespace App\Services\Admin;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Accesso al pannello: login, link di reimpostazione, nuova password.
 *
 * Il broker e la tabella dei token sono quelli del sito (config/auth.php):
 * cambia solo dove punta il link — al pannello, non alla pagina pubblica — e
 * chi lo riceve: solo gli amministratori. A chiunque altro non parte niente,
 * ma la risposta è identica, così il form non dice quali indirizzi esistono.
 */
class AdminAuthService
{
    /** Tentativi di login falliti prima del blocco (copy del design: "dopo cinque tentativi"). */
    public const MAX_ATTEMPTS = 5;

    /** Durata del blocco, in secondi ("per quindici minuti"). */
    public const LOCKOUT_SECONDS = 15 * 60;

    /** Richieste di link consentite per IP in un minuto. */
    private const MAX_RESET_REQUESTS_PER_IP = 5;

    /**
     * @throws ValidationException credenziali errate, account non amministratore, blocco attivo
     */
    public function attempt(string $email, string $password, bool $remember, string $ip): void
    {
        $key = $this->throttleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'email' => "Troppi tentativi. Riprova fra {$minutes} minuti.",
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        // Un cliente o un partner con la password giusta vede lo stesso errore
        // di chi l'ha sbagliata: il pannello non conferma chi ha un account.
        if ($user === null || ! $this->canAccessPanel($user) || ! Auth::validate(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email o password non corretti. Dopo cinque tentativi l\'accesso si blocca per quindici minuti.',
            ]);
        }

        RateLimiter::clear($key);
        Auth::login($user, $remember);
    }

    public function canAccessPanel(User $user): bool
    {
        return $user->is_active && $user->anonymized_at === null && $user->hasRole('superadmin');
    }

    /** Sempre la stessa risposta al chiamante, qualunque sia l'esito. */
    public function sendResetLink(string $email, string $ip): void
    {
        $key = 'admin-password-reset|'.$ip;

        if (RateLimiter::tooManyAttempts($key, self::MAX_RESET_REQUESTS_PER_IP)) {
            throw ValidationException::withMessages([
                'email' => 'Troppe richieste. Riprova fra un minuto.',
            ]);
        }

        RateLimiter::hit($key);

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $this->canAccessPanel($user)) {
            return;
        }

        // Callback del broker: il token lo crea Laravel (con il suo throttle
        // per email), la mail e il link li decidiamo noi.
        Password::sendResetLink(['email' => $email], function (User $user, string $token): void {
            $link = route('admin.password.reset', ['token' => $token, 'email' => $user->email]);

            Mail::to($user->email)->send(
                (new ResetPasswordMail($user, $link, $this->expiresInMinutes()))->locale('it')
            );
        });
    }

    /** Il link è ancora buono? Serve a mostrare "Link non più valido" prima del form. */
    public function tokenIsValid(string $email, string $token): bool
    {
        $user = User::query()->where('email', $email)->first();

        return $user !== null && $this->canAccessPanel($user) && Password::tokenExists($user, $token);
    }

    /**
     * @return string una costante di Password (PASSWORD_RESET, INVALID_TOKEN, INVALID_USER)
     */
    public function reset(string $email, string $token, string $password): string
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $this->canAccessPanel($user)) {
            return Password::INVALID_USER;
        }

        return Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user) use ($password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );
    }

    public function expiresInMinutes(): int
    {
        $broker = config('auth.defaults.passwords');

        return (int) config("auth.passwords.{$broker}.expire", 60);
    }

    private function throttleKey(string $email, string $ip): string
    {
        return 'admin-login|'.Str::lower($email).'|'.$ip;
    }
}
