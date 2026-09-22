<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Flusso "password dimenticata": richiesta del link, invio della mail
 * brandizzata, reimpostazione della password dal token.
 *
 * Il token e la sua scadenza restano gestiti dal broker di Laravel
 * (config/auth.php → passwords.users, tabella password_reset_tokens): qui
 * vivono solo le regole del portale (nessun oracolo di enumerazione, throttle
 * per IP, mail AnimalAmo al posto della notifica di default).
 */
class PasswordResetService
{
    /** Tentativi di richiesta link consentiti per IP in una finestra di un minuto. */
    private const MAX_REQUESTS_PER_IP = 5;

    /**
     * Broker del link di benvenuto dei partner creati dal pannello
     * (config/auth.php → passwords.partner_welcome, 7 giorni).
     */
    public const WELCOME_BROKER = 'partner_welcome';

    /**
     * Accoda l'invio del link di reimpostazione.
     *
     * L'esito del broker NON viene propagato: che l'email sia registrata, che
     * sia sconosciuta o che il throttle di 60 secondi abbia bloccato il secondo
     * invio, il chiamante riceve sempre la stessa conferma. Distinguere i casi
     * trasformerebbe la modale in un oracolo per sapere quali indirizzi hanno
     * un account AnimalAmo (stesso motivo per cui il login mostra un unico
     * messaggio di credenziali errate).
     */
    public function sendResetLink(string $email): void
    {
        $this->ensureIsNotRateLimited();

        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Invio effettivo della mail. Ci arriva il broker passando per
     * User::sendPasswordResetNotification(): è il punto in cui sostituiamo la
     * notifica inglese del framework con la nostra Mailable localizzata.
     */
    public function mailResetLink(User $user, string $token): void
    {
        // route() è dentro il gruppo localizzato di mcamara: il link nasce già
        // con il prefisso della lingua in cui l'utente ha chiesto il reset.
        $link = route('password.reset', ['token' => $token, 'email' => $user->email]);

        Mail::to($user->email)->send(
            (new ResetPasswordMail($user, $link, $this->expiresInMinutes()))->locale(app()->getLocale())
        );
    }

    /**
     * Il broker di benvenuto vale solo per un partner che non sia anche
     * amministratore. Il token non sa quale broker l'ha creato, e la tabella
     * è condivisa: senza questo controllo, `?welcome=1` allungherebbe a 7
     * giorni anche il link di reset di un superadmin (AdminAuthService, 60
     * minuti) e gli farebbe scegliere la password con la regola del sito,
     * più debole di quella del pannello.
     *
     * Col token, l'esito dipende anche da lui. La pagina è pubblica e la
     * risposta decide la copia a schermo: senza questa seconda condizione,
     * `?email=…&welcome=1` con un token inventato scriverebbe "Benvenuto su
     * AnimalAmo" solo per gli indirizzi che hanno un account partner — un
     * oracolo di enumerazione, proprio quello che sendResetLink() evita
     * ingoiando l'esito del broker. Chi ha ricevuto la mail il token ce
     * l'ha, quindi per lui non cambia niente.
     *
     * @param  string|null  $token  token del link; null = solo email e ruoli (guardia lato server di reset())
     */
    public function acceptsWelcome(string $email, ?string $token = null): bool
    {
        $user = User::query()->whereRaw('lower(email) = ?', [Str::lower(trim($email))])->first();

        if ($user === null || ! $user->hasRole('partner') || $user->hasRole('superadmin')) {
            return false;
        }

        return $token === null || Password::broker(self::WELCOME_BROKER)->tokenExists($user, $token);
    }

    /**
     * Consuma il token e imposta la nuova password.
     *
     * Il broker va scelto da chi chiama: la scadenza la verifica il broker con
     * la SUA durata, e la tabella è condivisa. Un token di benvenuto
     * consumato col broker di default scadrebbe dopo 60 minuti.
     * WELCOME_BROKER per un account che non è partner, o che è anche
     * amministratore, torna al default (acceptsWelcome()).
     *
     * Rischio residuo accettato: per un partner, aggiungere `welcome=1` a un
     * link di "password dimenticata" ne allunga la finestra a 7 giorni. Il
     * token resta segreto, monouso e recapitato solo al titolare dell'email.
     *
     * @param  string|null  $broker  null = broker di default (`users`); self::WELCOME_BROKER per il benvenuto
     * @return string una costante di Password (PASSWORD_RESET, INVALID_TOKEN, INVALID_USER)
     */
    public function reset(string $email, string $token, string $password, ?string $broker = null): string
    {
        if ($broker === self::WELCOME_BROKER && ! $this->acceptsWelcome($email)) {
            $broker = null;
        }

        return Password::broker($broker)->reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (User $user) use ($password): void {
                // Il cast 'hashed' del model fa l'hash; remember_token ruotato
                // invalida i cookie "ricordami" rimasti su altri dispositivi
                // (le sessioni attive non sono raggiungibili da qui: chi
                // reimposta la password non è autenticato).
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );
    }

    /** Minuti di validità del link, dalla configurazione del broker attivo. */
    public function expiresInMinutes(): int
    {
        $broker = config('auth.defaults.passwords');

        return (int) config("auth.passwords.{$broker}.expire", 60);
    }

    /**
     * Il throttle del broker è per email: da solo lascerebbe passare una
     * scansione di indirizzi diversi dallo stesso client. Questo è per IP.
     */
    private function ensureIsNotRateLimited(): void
    {
        $key = 'password-reset|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS_PER_IP)) {
            throw ValidationException::withMessages([
                'email' => trans('passwords.throttled'),
            ]);
        }

        RateLimiter::hit($key);
    }
}
