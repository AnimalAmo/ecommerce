<?php

namespace App\Services\Partner;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PaymentModeException;
use App\Jobs\PublishAwaitingDrafts;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Modalità di pagamento del partner (richiesta della cliente, 22/09/2026):
 * online su AnimalAmo, oppure direttamente al partner, in struttura o sul suo
 * sito. Tiene in un posto solo la regola del cambio e la lettura. Schede,
 * carrello e checkout la chiedono più volte nella stessa richiesta, per
 * questo il service è registrato `scoped` e si ricorda i profili già letti.
 */
class PartnerPaymentModeService
{
    /** Regole del "sito dove pagare o prenotare", condivise coi form che lo scrivono. */
    public const PAYMENT_URL_RULES = ['nullable', 'string', 'max:255', 'url:http,https'];

    /** @var array<int, PartnerProfile|null> profili letti per user id, anche quelli assenti */
    private array $profiles = [];

    /**
     * Si rifiuta solo il passaggio da offline a online di chi non è pagabile.
     * Chi è già online senza Stripe (appena iscritto, o creato dall'admin) può
     * salvare il link senza che gli si chieda altro. Passare a offline è sempre
     * permesso: gli ordini online passati continuano a essere bonificati,
     * perché canBePaid() non cambia.
     *
     * Il link finisce come href nelle pagine B2C e nelle mail: lo si verifica
     * qui, per ogni chiamante, anche se il form lo ha già validato.
     *
     * La modalità e la vetrina delle smartbox si scrivono nella stessa
     * transazione (difetto C8, vedi syncSmartboxWithholding()): un partner
     * offline con un cofanetto ancora in vendita è proprio lo stato da evitare.
     * La dispatch delle bozze resta fuori, dopo il commit, per la ragione
     * scritta su publishAwaitingDrafts().
     *
     * @throws PaymentModeException
     * @throws ValidationException link non http/https o troppo lungo, sotto la chiave `paymentUrl`
     */
    public function set(PartnerProfile $profile, bool $online, ?string $paymentUrl): PartnerProfile
    {
        if ($online && ! $profile->canSwitchToOnline()) {
            throw PaymentModeException::stripeRequired();
        }

        $url = trim((string) $paymentUrl);
        $url = $url === '' ? null : $url;

        Validator::make(['paymentUrl' => $url], ['paymentUrl' => self::PAYMENT_URL_RULES])->validate();

        DB::transaction(function () use ($profile, $online, $url): void {
            $profile->fill([
                'online_payment' => $online,
                'payment_url' => $url,
            ])->save();

            $this->syncSmartboxWithholding($profile);
        });

        $this->profiles[(int) $profile->user_id] = $profile;

        $this->publishAwaitingDrafts($profile);

        return $profile;
    }

    /** Senza profilo (o senza titolare) vale il comportamento di prima: online. */
    public function forOwner(?int $userId): OrderPaymentMode
    {
        return $this->profileFor($userId)?->paymentMode() ?? OrderPaymentMode::Online;
    }

    /**
     * Modalità di molti titolari in una query sola. Le griglie del catalogo e le
     * card dei preferiti devono decidere per decine di righe se il pulsante
     * carrello ha senso: una lettura per riga sarebbe una N+1 nascosta dietro
     * una vista.
     *
     * Come profileFor(), la cache tiene anche l'ASSENZA di profilo (null): sono
     * gli id senza profilo quelli che, non memorizzati, tornerebbero a
     * interrogare il database a ogni forOwner() successivo.
     *
     * @param  iterable<int|string|null>  $userIds  anche con null e duplicati dentro
     * @return array<int, OrderPaymentMode> mappa id titolare => modalità (gli id null non ci sono)
     */
    public function forOwners(iterable $userIds): array
    {
        $ids = [];

        foreach ($userIds as $userId) {
            if ($userId !== null && ! in_array((int) $userId, $ids, true)) {
                $ids[] = (int) $userId;
            }
        }

        $missing = array_values(array_filter(
            $ids,
            fn (int $id): bool => ! array_key_exists($id, $this->profiles),
        ));

        if ($missing !== []) {
            $found = PartnerProfile::query()
                ->whereIn('user_id', $missing)
                ->get()
                ->keyBy(fn (PartnerProfile $profile): int => (int) $profile->user_id);

            foreach ($missing as $id) {
                $this->profiles[$id] = $found->get($id);
            }
        }

        $modes = [];

        foreach ($ids as $id) {
            $modes[$id] = $this->forOwner($id);
        }

        return $modes;
    }

    /** Structure, Event o SmartboxPackage: decide il titolare della scheda. */
    public function forPurchasable(?Model $purchasable): OrderPaymentMode
    {
        $owner = $purchasable?->getAttribute('user_id');

        return $this->forOwner($owner === null ? null : (int) $owner);
    }

    /** Profilo del venditore, per ragione sociale e link nel checkout e nelle viste. */
    public function profileFor(?int $userId): ?PartnerProfile
    {
        if ($userId === null) {
            return null;
        }

        if (! array_key_exists($userId, $this->profiles)) {
            $this->profiles[$userId] = PartnerProfile::query()->where('user_id', $userId)->first();
        }

        return $this->profiles[$userId];
    }

    /**
     * Il gemello del ritiro. Difetto C8 dell'audit del 27/09/2026, corretto il
     * 28/09/2026: fino a qui `withheld_at` la scriveva solo la migrazione-dati
     * una-tantum (2026_09_27_100002), e set() non toccava nessuna riga di
     * catalogo. Un partner che passava al pagamento diretto DOPO la migrazione
     * non poteva più pubblicare una smartbox nuova, ma quella già in vetrina
     * restava in /smartbox, con la card «prenota col partner» al posto del
     * pulsante: un invito che a un cofanetto prepagato da regalare non si
     * applica. E restava aggiungibile al carrello con una chiamata forgiata.
     *
     * Le due direzioni:
     *  - modalità offline → si ritirano le smartbox del partner non ancora
     *    ritirate. Le bozze NON si segnano (review del 28/09/2026): il segnale
     *    di pubblicazione fa ripubblicare la bozza al ritorno online, e una
     *    bozza completata riaperta e lasciata a metà nel wizard sarebbe andata
     *    online così, rimandando in moderazione una scheda già approvata. Il
     *    ritorno lo fa il ramo qui sotto, direttamente sulla riga; l'avviso in
     *    dashboard conta le righe ritirate (Partner\Dashboard);
     *  - smartbox di nuovo pubblicabile (online E pagabile) → si annulla il
     *    ritiro sulle righe del partner. Direttamente e non solo tramite la
     *    ripubblicazione: una riga senza bozza (le smartbox della finestra in
     *    cui il wizard era aperto agli ospiti, o una creata a mano) non ha un
     *    segnale su cui ripartire e resterebbe fuori per sempre; e con la coda
     *    asincrona il cofanetto torna in vetrina subito, non al giro del job.
     *
     * Solo la modalità offline ritira. Chi resta online senza Stripe operativo
     * (canSwitchToOnline lo lascia salvare il link) non perde le sue smartbox
     * da qui: il suo ritorno pagabile passa dal webhook di Stripe, che conosce
     * solo le bozze col segnale, e un ritiro scritto qui non tornerebbe più
     * indietro. Strutture ed eventi non si toccano mai: si vendono anche in
     * struttura (vedi PartnerProfile::canPublishFamily()).
     *
     * Update di massa col query builder e withHidden(): servono proprio le
     * righe già fuori dal sito (sospese, in attesa), e il ritiro è una colonna
     * indipendente dalla sospensione dell'admin — annullarlo non riattiva una
     * scheda sospesa.
     */
    private function syncSmartboxWithholding(PartnerProfile $profile): void
    {
        if ($profile->user_id === null) {
            return;
        }

        $boxes = fn () => SmartboxPackage::withHidden()->where('user_id', $profile->user_id);

        if (! $profile->requiresOnlinePayment()) {
            $boxes()->whereNull('withheld_at')->update(['withheld_at' => now()]);

            return;
        }

        if ($profile->canPublishFamily('smartbox')) {
            $boxes()->whereNotNull('withheld_at')->update(['withheld_at' => null]);
        }
    }

    /**
     * Passare al pagamento diretto, o tornare online da pagabile, può
     * sbloccare i servizi rimasti in attesa di Stripe (P4). La dispatch parte
     * solo se ce n'è almeno uno. Stessa protezione di StripeConnectService:
     * con la coda sync il job gira qui dentro, e un suo errore non deve far
     * sembrare fallito un cambio di modalità già salvato.
     *
     * Attenzione per P2/P3: se set() viene chiamato dentro un DB::transaction
     * esterno, con afterCommit il job gira al commit, fuori da questo
     * try/catch, e un suo errore risale al chiamante.
     */
    private function publishAwaitingDrafts(PartnerProfile $profile): void
    {
        if ($profile->user_id === null || ! $profile->canPublish()) {
            return;
        }

        // Non `exists()`: dal 27/09/2026 il gate è per famiglia, quindi avere
        // qualcosa in attesa non basta — serve avere qualcosa di pubblicabile.
        // Un partner che passa al pagamento diretto con in attesa solo smartbox
        // metterebbe in coda un job che non pubblica niente. Bastano id e
        // categoria: `family()` non legge altro.
        $publishable = StructureDraft::query()
            ->where('user_id', $profile->user_id)
            ->awaitingPublication()
            ->get(['id', 'service_category'])
            ->contains(fn (StructureDraft $draft): bool => $profile->canPublishFamily($draft->family()));

        if (! $publishable) {
            return;
        }

        try {
            PublishAwaitingDrafts::dispatch((int) $profile->user_id);
        } catch (Throwable $exception) {
            Log::warning('Pubblicazione delle bozze in attesa non avviata', [
                'partner_user_id' => $profile->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
