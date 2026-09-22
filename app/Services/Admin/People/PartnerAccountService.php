<?php

namespace App\Services\Admin\People;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Partner\RegisterPartnerAccount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        $user = $this->registrar->register([
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
        ], $existing, null);

        // Rilettura: per un cliente promosso la relazione era già caricata
        // (vuota) dal controllo qui sopra.
        $profile = $user->partnerProfile()->firstOrFail();
        $user->setRelation('partnerProfile', $profile);

        $paymentModeSaved = true;

        if ($url !== null || $profile->requiresOnlinePayment() !== $online) {
            $paymentModeSaved = $this->applyPaymentMode($profile, $online, $url);
        }

        return new CreatedPartner($user, $existing !== null, $paymentModeSaved);
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
}
