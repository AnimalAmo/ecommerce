<?php

namespace App\Services\Admin\People;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Mail\PartnerWelcomeMail;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Partner\RegisterPartnerAccount;
use App\Services\PasswordResetService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Throwable;

/**
 * Partner creati dal pannello (richiesta della cliente, 22/09/2026): chi
 * chiama o scrive per diventare partner non passa più per forza dal modulo
 * del sito.
 *
 * La registrazione è quella del sito (RegisterPartnerAccount), senza login:
 * l'account nasce con una password casuale che nessuno conosce, e si usa solo
 * dopo averne scelta una dal link di benvenuto.
 *
 * Non è una sola transazione (deviazione dichiarata dal contratto): la
 * registrazione fa commit da sé, la modalità di pagamento si salva dopo.
 */
class PartnerAccountService
{
    /** "Invia di nuovo il link": uno al minuto per partner, contro i doppi clic e le raffiche. */
    public const RESEND_DECAY_SECONDS = 60;

    public function __construct(
        private readonly RegisterPartnerAccount $registrar,
        private readonly PartnerPaymentModeService $modes,
    ) {}

    /**
     * L'email si confronta in minuscolo: su SQLite `=` distingue le
     * maiuscole (su MySQL no), e le righe scritte prima della
     * normalizzazione possono averne. Chi è già nel sistema:
     *
     *  - amministratore → rifiuto (il pannello non gestisce i suoi pari);
     *  - partner → rifiuto, con l'account per aprirne la scheda;
     *  - disattivato o anonimizzato → rifiuto: promuoverlo lo lascerebbe
     *    disattivato (promote() non riattiva nessuno) e l'area partner gli
     *    risponderebbe 403;
     *  - cliente attivo → promozione, resta anche cliente.
     *
     * Link e modalità si verificano PRIMA di scrivere, così un rifiuto non
     * lascia account a metà. La modalità si salva DOPO il commit della
     * registrazione: set() fa partire un job afterCommit (P4), che dentro una
     * transazione esterna girerebbe al commit, fuori dal try/catch di set().
     *
     * @param  array<string, mixed>  $data  chiavi di PartnerCreateForm (camelCase) + paymentMode, paymentUrl
     *
     * @throws PartnerAccountException
     * @throws PaymentModeException cliente con profilo offline che chiede online senza Stripe
     * @throws ValidationException link non http/https, sotto la chiave `paymentUrl`
     */
    public function create(array $data): CreatedPartner
    {
        $email = Str::lower(trim((string) $data['email']));
        $online = ($data['paymentMode'] ?? OrderPaymentMode::Online->value) === OrderPaymentMode::Online->value;
        $url = trim((string) ($data['paymentUrl'] ?? ''));
        $url = $url === '' ? null : $url;

        Validator::make(['paymentUrl' => $url], ['paymentUrl' => PartnerPaymentModeService::PAYMENT_URL_RULES])->validate();

        $existing = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        $this->guard($existing);

        $current = $existing?->partnerProfile;

        if ($online && $current !== null && ! $current->canSwitchToOnline()) {
            throw PaymentModeException::stripeRequired();
        }

        $user = $this->register($email, $existing, [
            'firstName' => (string) $data['firstName'],
            'lastName' => (string) $data['lastName'],
            'businessName' => (string) $data['businessName'],
            'email' => $email,
            'address' => (string) $data['address'],
            'province' => (string) $data['province'],
            'zip' => (string) $data['zip'],
            'phone' => (string) $data['phone'],
            'vat' => (string) $data['vat'],
            'taxCode' => (string) $data['taxCode'],
            'onlinePayment' => $online,
        ]);

        // Rilettura: per un cliente promosso la relazione era già caricata
        // (vuota) dal controllo qui sopra.
        $profile = $user->partnerProfile()->firstOrFail();
        $user->setRelation('partnerProfile', $profile);

        $paymentModeSaved = true;

        // Anche il link va confrontato, non solo "è stato scritto qualcosa":
        // su un profilo che esisteva già, un campo lasciato vuoto deve
        // cancellare il link di prima, non lasciarlo vivo.
        if ($url !== $profile->payment_url || $profile->requiresOnlinePayment() !== $online) {
            $paymentModeSaved = $this->applyPaymentMode($profile, $online, $url);
        }

        $promoted = $existing !== null;

        // Registrazione già committata: token e mail non possono anticiparla.
        // L'account esiste comunque: se la mail non parte, l'admin la rimanda dalla scheda.
        try {
            $this->sendWelcome($user, $promoted);
            $welcomeSent = true;
        } catch (Throwable $e) {
            report($e);
            $welcomeSent = false;
        }

        return new CreatedPartner($user, $promoted, $paymentModeSaved, $welcomeSent);
    }

    /**
     * Token con createToken() e non sendResetLink(): niente throttle del
     * broker per email e niente limite per IP di PasswordResetService, che
     * fermerebbe un admin che crea più partner di fila. Un token precedente
     * della stessa email (anche di "password dimenticata") viene sostituito.
     *
     * @param  bool  $promoted  cliente promosso: ha già una password, riceve solo l'avviso
     */
    public function sendWelcome(User $partner, bool $promoted = false): void
    {
        $url = $promoted
            ? null
            : $this->setPasswordUrl($partner, Password::broker(PasswordResetService::WELCOME_BROKER)->createToken($partner));

        Mail::to($partner->email)->send(new PartnerWelcomeMail($partner, $url));
    }

    /**
     * Nuovo link "scegli la password" dalla scheda del partner: il primo è
     * scaduto, finito nello spam, o il partner ha perso la mail. Anche a un
     * cliente promosso: un link per scegliere una password nuova non gli
     * toglie quella che ha. Solo per partner attivi: un disattivato non
     * entrerebbe comunque nell'area partner, e a chi non è partner la pagina
     * non darebbe i 7 giorni (PasswordResetService::acceptsWelcome).
     *
     * @throws PartnerAccountException notPartner(), inactive() o throttled()
     */
    public function resendWelcome(User $partner): void
    {
        if (! $partner->hasRole('partner')) {
            throw PartnerAccountException::notPartner();
        }

        if (! $partner->is_active || $partner->anonymized_at !== null) {
            throw PartnerAccountException::inactive();
        }

        $key = 'partner-welcome|'.$partner->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw PartnerAccountException::throttled();
        }

        RateLimiter::hit($key, self::RESEND_DECAY_SECONDS);

        $this->sendWelcome($partner);
    }

    /**
     * Fra il controllo e la scrittura c'è una finestra: due admin sullo stesso
     * indirizzo passano entrambi guard(), e il secondo trova l'unique di
     * users.email. Senza questo, chi perde la corsa vedrebbe un 500 invece del
     * messaggio del flusso. Si rilegge la riga: se ora è di un partner, è la
     * corsa persa; altrimenti il vincolo parla d'altro e l'errore risale.
     *
     * @param  array<string, mixed>  $step1
     *
     * @throws PartnerAccountException
     */
    private function register(string $email, ?User $existing, array $step1): User
    {
        try {
            return $this->registrar->register($step1, $existing, null);
        } catch (UniqueConstraintViolationException $e) {
            $winner = User::query()->whereRaw('lower(email) = ?', [$email])->first();

            if ($winner === null || ! $winner->hasRole('partner')) {
                throw $e;
            }

            throw PartnerAccountException::alreadyPartner($winner);
        }
    }

    /** @throws PartnerAccountException */
    private function guard(?User $existing): void
    {
        if ($existing === null) {
            return;
        }

        if ($existing->hasRole('superadmin')) {
            throw PartnerAccountException::superadmin();
        }

        if ($existing->hasRole('partner')) {
            throw PartnerAccountException::alreadyPartner($existing);
        }

        if (! $existing->is_active || $existing->anonymized_at !== null) {
            throw PartnerAccountException::inactive();
        }
    }

    /**
     * Link e cambio sono già verificati: qui può fallire solo quello che set()
     * fa partire. L'account esiste ormai, e deve ricevere la mail lo stesso:
     * si segnala, si prosegue, e chi chiama avvisa l'admin.
     */
    private function applyPaymentMode(PartnerProfile $profile, bool $online, ?string $url): bool
    {
        try {
            $this->modes->set($profile, $online, $url);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * La pagina pubblica di reset, nella lingua di default, come fa
     * PartnerNewBookingMail. Con route() il link seguirebbe le rotte
     * registrate dalla richiesta, e l'admin è fuori da mcamara. Email e
     * `welcome` in query, come le legge ResetPassword::mount().
     */
    private function setPasswordUrl(User $partner, string $token): string
    {
        $page = (string) LaravelLocalization::getURLFromRouteNameTranslated(
            LaravelLocalization::getDefaultLocale(),
            'routes.password.reset',
            ['token' => $token],
        );

        return $page.'?'.http_build_query(['email' => $partner->email, 'welcome' => 1]);
    }
}
