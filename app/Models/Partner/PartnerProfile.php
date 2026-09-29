<?php

namespace App\Models\Partner;

use App\Enums\OrderPaymentMode;
use App\Models\User;
use Database\Factories\Partner\PartnerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Profilo B2B (1:1 con {@see User} di ruolo partner): dati fiscali + coordinate
 * di pagamento mostrati/modificati nelle pagine Profilo del partner.
 */
class PartnerProfile extends Model
{
    /** @use HasFactory<PartnerProfileFactory> */
    use HasFactory, HasTranslations;

    /**
     * Orari di apertura o disponibilità, localizzati (richiesta della cliente,
     * 27/09/2026): un campo solo, sul partner, perché li nomina sia fra i campi
     * dell'attività sia fra i recapiti pubblici e due campi che possono
     * contraddirsi sono peggio di uno. Le schede li leggono da qui.
     *
     * SOLO colonne stringa, come sugli altri model: spatie tratterebbe un array
     * come mappa di locale. E un attributo tradotto legge `''` e non `null`
     * quando la lingua manca — si controlla con blank()/filled().
     */
    public array $translatable = ['opening_hours'];

    protected $fillable = [
        'business_name',
        'vat',
        'tax_code',
        'address',
        'city',
        'province',
        'zip',
        'account_holder',
        'iban',
        'bic',
        'stripe_account_id',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
        'stripe_requirements_due',
        'commission_rate_bp',
        'commission_min_cents',
        'online_payment',
        'payment_url',
        'opening_hours',
        // Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6):
        // voci nuove, mai quelle di registrazione o fatturazione. Si mostrano
        // solo col consenso, vedi publishesContacts().
        'public_phone',
        'public_whatsapp',
        'public_email',
        'public_website',
        'public_address',
        'public_contacts_consent_at',
        // Tipologia scelta nello step 2 dell'iscrizione, conservata per
        // preselezionare la card giusta al primo "Crea servizio" (richiesta
        // della cliente, 27/09/2026). Stringa nullable: nessun cast serve.
        'registration_service',
    ];

    protected function casts(): array
    {
        return [
            'online_payment' => 'boolean',
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
            'stripe_requirements_due' => 'array',
            'commission_rate_bp' => 'integer',
            'commission_min_cents' => 'integer',
            'public_contacts_consent_at' => 'datetime',
        ];
    }

    /**
     * Il partner ha acconsentito a pubblicare i suoi recapiti pubblici
     * (risposta della cliente, 26/09/2026, punto 6). Senza consenso restano
     * salvati ma non compaiono sulle schede. Gli orari non passano da qui: il
     * partner li scrive già sapendo che si vedono.
     */
    public function publishesContacts(): bool
    {
        return $this->public_contacts_consent_at !== null;
    }

    /** Può incassare: l'onboarding Stripe è arrivato a charges_enabled. */
    public function canSell(): bool
    {
        return $this->stripe_account_id !== null && $this->stripe_charges_enabled;
    }

    /** Può ricevere bonifici: serve anche payouts_enabled (IBAN verificato su Stripe). */
    public function canBePaid(): bool
    {
        return $this->canSell() && $this->stripe_payouts_enabled;
    }

    /**
     * Il cliente paga online su AnimalAmo. `!== false` e non `=== true`: un
     * profilo appena creato non ha la colonna in memoria (la scrive il default
     * del database), e va letto come i partner di prima, cioè online.
     */
    public function requiresOnlinePayment(): bool
    {
        return $this->online_payment !== false;
    }

    /**
     * Può mandare servizi a catalogo. Chi si fa pagare direttamente non ha
     * bisogno di Stripe. canSell/canBePaid restano verifiche Stripe pure,
     * perché decidono anche i bonifici degli ordini online già fatti.
     */
    public function canPublish(): bool
    {
        return ! $this->requiresOnlinePayment() || $this->canBePaid();
    }

    /**
     * Può mandare a catalogo un servizio di questa famiglia.
     *
     * La smartbox è l'eccezione (richiesta della cliente, 27/09/2026: «una
     * Smartbox non deve poter essere pubblicata/acquistata se il partner non ha
     * collegato un sistema di pagamento»): è un cofanetto prepagato, quindi
     * serve l'incasso online E un account Stripe pagabile. Con il pagamento
     * diretto finirebbe nel percorso "paghi in struttura", che per un prepagato
     * non esiste: non sarebbe acquistabile, e ciò che non è acquistabile non si
     * tiene in vetrina.
     *
     * Il parametro è il valore di `StructureDraft::family()`
     * (struttura|attivita|smartbox). Va bene anche la `service_category` del
     * pannello: solo `smartbox` è un caso a sé, tutto il resto cade sul default.
     *
     * `canPublish()` resta com'è: la leggono payout, checkout, ConnectReadiness
     * e la pagina profilo, dove la famiglia non esiste.
     */
    public function canPublishFamily(string $family): bool
    {
        if ($family === 'smartbox') {
            return $this->requiresOnlinePayment() && $this->canBePaid();
        }

        return $this->canPublish();
    }

    /**
     * La famiglia è ferma per la modalità di incasso, non per Stripe: è una
     * smartbox e il partner si fa pagare direttamente. Collegare Stripe non
     * basta, deve passare all'incasso online. Quando è vero,
     * `canPublishFamily()` è sempre falso; quando `canPublishFamily()` è falso
     * e questo no, la causa è Stripe (onboarding a metà o conto non pagabile).
     *
     * `canPublishFamily()` comprime le due cause in un booleano e DraftPublisher
     * le riapre per scegliere l'eccezione. Difetti F2 e F3 dell'audit dei
     * flussi (28/09/2026): il messaggio di fine wizard, i banner della
     * dashboard e il badge di "I miei servizi" leggevano il solo booleano. Chi
     * incassa in struttura leggeva «completa il collegamento Stripe», lo
     * collegava e la smartbox restava ferma; chi è online senza Stripe vedeva
     * due banner per lo stesso fatto. Da qui leggono la causa tutti e tre.
     */
    public function needsOnlinePaymentFor(string $family): bool
    {
        return $family === 'smartbox' && ! $this->requiresOnlinePayment();
    }

    /**
     * Può scegliere (o tenere) il pagamento online. Resta online chi lo è già,
     * anche senza Stripe (appena iscritto, o creato dall'admin); chi è offline
     * ci torna solo da pagabile. Una sola regola per il service, che rifiuta,
     * e per la pagina profilo, che disabilita la scelta.
     */
    public function canSwitchToOnline(): bool
    {
        return $this->requiresOnlinePayment() || $this->canBePaid();
    }

    public function paymentMode(): OrderPaymentMode
    {
        return $this->requiresOnlinePayment() ? OrderPaymentMode::Online : OrderPaymentMode::OnSite;
    }

    /** Aliquota del partner, o quella di piattaforma se non deroga. */
    public function commissionRateBp(): int
    {
        return $this->commission_rate_bp ?? (int) config('commerce.commission.rate_bp');
    }

    /** Soglia del partner, o quella di piattaforma se non deroga (0 è una deroga vera). */
    public function commissionMinCents(): int
    {
        return $this->commission_min_cents ?? (int) config('commerce.commission.min_cents');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
