<?php

namespace App\Services\Admin\People;

use App\Exceptions\AdminPasswordLinkException;
use App\Mail\AdminPasswordLinkMail;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Link "imposta una nuova password" dalla scheda di un iscritto: l'utente ha
 * perso l'accesso e chiede aiuto all'amministratore. L'amministratore non
 * sceglie né vede nessuna password, e nessuna password viaggia nella mail:
 * l'utente la sceglie dal link (broker admin_reset, 7 giorni). Rimandarlo
 * genera un token nuovo, e il precedente smette di valere (tabella per email).
 * Fino al reset la password attuale resta valida: per bloccare subito un
 * account c'è "Disattiva".
 */
class AdminPasswordLinkService
{
    /** Un invio al minuto per iscritto, come il reinvio del benvenuto partner. */
    private const RESEND_DECAY_SECONDS = 60;

    /**
     * @throws AdminPasswordLinkException superadmin(), inactive() o throttled()
     */
    public function send(User $user): void
    {
        if ($user->hasRole('superadmin')) {
            throw AdminPasswordLinkException::superadmin();
        }

        if (! $user->is_active || $user->anonymized_at !== null) {
            throw AdminPasswordLinkException::inactive();
        }

        $key = 'admin-password-link|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw AdminPasswordLinkException::throttled();
        }

        RateLimiter::hit($key, self::RESEND_DECAY_SECONDS);

        $token = Password::broker(PasswordResetService::ADMIN_BROKER)->createToken($user);

        Mail::to($user->email)->send(new AdminPasswordLinkMail($user, $this->link($user, $token)));
    }

    /**
     * La pagina pubblica di reset nella lingua di default, come
     * PartnerAccountService::setPasswordUrl(): l'admin è fuori da mcamara.
     * Email e `admin` in query, come li legge ResetPassword::mount().
     */
    private function link(User $user, string $token): string
    {
        $page = (string) LaravelLocalization::getURLFromRouteNameTranslated(
            LaravelLocalization::getDefaultLocale(),
            'routes.password.reset',
            ['token' => $token],
        );

        return $page.'?'.http_build_query(['email' => $user->email, 'admin' => 1]);
    }
}
