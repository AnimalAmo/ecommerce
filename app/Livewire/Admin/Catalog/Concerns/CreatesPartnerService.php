<?php

namespace App\Livewire\Admin\Catalog\Concerns;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Exceptions\PartnerServiceException;
use App\Livewire\Concerns\HandlesPhotoUploads;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\Catalog\AdminServiceCreator;
use App\Services\Admin\People\UserDirectory;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * Parte comune dei tre componenti che creano una scheda dal pannello
 * (struttura/servizi, attività, smartbox): scelta del partner, riepilogo,
 * foto e salvataggio. Le famiglie ci mettono solo i campi.
 *
 * DOVE STA LA VALIDAZIONE: dentro `draftAttributes()`, come sua prima riga.
 * Il trait la chiama qui sotto in un try/catch e solo DOPO chiama
 * `collectPhotos()` — così nessun file arriva su disco prima che tutte le
 * sezioni abbiano validato (spec §5.3). Le famiglie non espongono nessun
 * `validateSections()`: c'è un posto solo, ed è questo.
 *
 * Niente "salva bozza": il form crea e pubblica in un colpo solo, quindi non
 * c'è nessuna bozza a cui attaccare le foto mentre si compila. Per questo
 * `draft()` torna un StructureDraft vuoto e mai salvato — HandlesPhotoUploads
 * lo usa solo per leggere le foto già memorizzate, che qui sono sempre zero.
 */
trait CreatesPartnerService
{
    use HandlesPhotoUploads;

    /**
     * Partner a cui si intesta la scheda. `#[Locked]`: arriva dall'indirizzo
     * (`?partner=`) o dal select tramite `updatedPartnerChoice()`, che
     * ricontrolla l'idoneità — il client non lo scrive mai direttamente, e un
     * test che facesse `->set('partnerId', ...)` prenderebbe una
     * CannotUpdateLockedPropertyException. Si passa sempre da `partnerChoice`.
     */
    #[Url(as: 'partner', except: null)]
    #[Locked]
    public ?int $partnerId = null;

    /** Valore del select: il DOM manda stringhe, anche per gli id. */
    public string $partnerChoice = '';

    /** Lingua attiva dei tab testi (it|en), un solo x-admin.tabs per pagina. */
    public string $lang = 'it';

    /** Avviso "in attesa di Stripe" mostrato al posto del redirect (vedi save()). */
    public ?string $pendingNotice = null;

    /**
     * L'esito di cui sopra è quello della smartbox di un partner che incassa
     * fuori dalla piattaforma? Serve al titolo dell'avviso, che altrimenti
     * resterebbe «non può ancora essere pagato» sopra un corpo che parla
     * d'altro: la diagnosi falsa che il 27/09/2026 doveva sparire.
     */
    public bool $pendingNoticeSmartbox = false;

    /**
     * Salvataggio già riuscito. Il percorso "in attesa di Stripe" non fa
     * redirect (vedi save()), quindi la pagina resta viva e compilata: un
     * secondo clic creerebbe una seconda bozza identica, con un'altra copia
     * delle stesse quattro foto. `#[Locked]`: lo scrive solo `save()`.
     */
    #[Locked]
    public bool $created = false;

    /** Bozza fittizia per HandlesPhotoUploads: privata, quindi fuori dal payload. */
    private ?StructureDraft $draftStub = null;

    private ?User $partnerModel = null;

    private ?Collection $eligible = null;

    public function mountCreatesPartnerService(): void
    {
        // Un `?partner=` scritto a mano, o un partner disattivato dopo che il
        // link è stato copiato, non deve arrivare fino al salvataggio.
        $this->setPartner($this->partnerId);
    }

    public function updatedPartnerChoice(string $value): void
    {
        $this->setPartner($value === '' ? null : (int) $value);
        $this->resetPartnerDependentState();
    }

    /** @return Collection<int, string> id => ragione sociale */
    public function eligiblePartners(): Collection
    {
        return $this->eligible ??= app(AdminServiceCreator::class)->eligiblePartners();
    }

    /**
     * Quello che serve al partial `partner-aside`, in una chiamata sola: le
     * tre viste fanno `...$this->partnerViewData()` dentro l'array della
     * `render()`.
     *
     * @return array{summary: ?array, eligible: Collection<int, string>}
     */
    public function partnerViewData(): array
    {
        return [
            'summary' => $this->partnerSummary(),
            'eligible' => $this->eligiblePartners(),
        ];
    }

    /**
     * Riquadro laterale: chi è il partner e cosa succederà alla scheda.
     * UserDirectory lo risolve il container perché le viste non ricevono
     * iniezioni.
     *
     * @return array{business_name: ?string, listings: int, suspended: int, withheld: int, bookings: int, payment_mode: string, stripe_status: string, can_publish: bool, smartbox_payment_block: bool}|null
     */
    public function partnerSummary(): ?array
    {
        $partner = $this->partner();

        if ($partner === null) {
            return null;
        }

        $summary = app(UserDirectory::class)->partnerSummary($partner);
        $profile = $partner->partnerProfile;

        return [
            ...$summary,
            // `stripe_status` c'è già in partnerSummary (payable|incomplete|
            // none). Il fallback resta perché la chiave è di P1/P2 e questo
            // riquadro non deve esplodere su un indice mancante.
            'stripe_status' => $summary['stripe_status'] ?? match (true) {
                (bool) $profile?->canBePaid() => 'payable',
                $profile?->stripe_account_id !== null => 'incomplete',
                default => 'none',
            },
            // Falso = la scheda nascerà "in attesa": l'avviso lo dice PRIMA
            // che l'admin compili undici sezioni. Per famiglia dal 27/09/2026:
            // su una smartbox il pagamento diretto non basta più, e leggere
            // "può pubblicare" su una pagina che poi lascia la scheda in attesa
            // è la bugia che l'avviso deve evitare.
            'can_publish' => (bool) $profile?->canPublishFamily($this->serviceCategory()),
            // Quale dei due avvisi: Stripe da finire, o modalità di pagamento
            // sbagliata per un cofanetto prepagato.
            'smartbox_payment_block' => $this->smartboxPaymentBlock(),
        ];
    }

    /**
     * La scheda è una smartbox e il partner incassa fuori dalla piattaforma: il
     * motivo del blocco non è Stripe ma la modalità di pagamento (richiesta
     * della cliente, 27/09/2026). Lo leggono l'avviso preventivo del riquadro
     * laterale e il messaggio di esito, che devono dire la stessa cosa.
     */
    private function smartboxPaymentBlock(): bool
    {
        return $this->serviceCategory() === 'smartbox'
            && $this->partner()?->partnerProfile?->requiresOnlinePayment() === false;
    }

    /**
     * Crea e pubblica. L'ordine conta: prima i campi (che si validano dentro
     * `draftAttributes()`), poi le foto (che si scrivono su disco e vanno
     * ripulite se qualcosa va storto), poi il service, che è l'unico a
     * toccare il database.
     */
    public function save(AdminServiceCreator $creator): void
    {
        if ($this->created) {
            return;
        }

        $this->pendingNotice = null;
        $this->pendingNoticeSmartbox = false;

        $partner = $this->partner();

        if ($partner === null) {
            $this->addError('partnerChoice', __('admin-catalog.create.partner_required', [], 'it'));

            return;
        }

        try {
            $attributes = $this->draftAttributes();
        } catch (ValidationException $e) {
            // Un errore sul testo inglese, col tab sull'italiano, è un errore
            // invisibile: il bottone sembra morto (vedi ArticleEdit).
            $this->focusLocaleOf(array_keys($e->errors()));

            throw $e;
        }

        // `saved` serve al wizard, che riapre una bozza con le foto già su
        // disco. Qui non c'è nessuna bozza da cui ereditarle, ed è una
        // proprietà pubblica: `collectPhotos()` la conta nel minimo di quattro
        // e la copia in `img` senza guardare se quei file esistono.
        $this->saved = [];

        $photos = $this->collectPhotos();

        if ($photos === null) {
            return;
        }

        $attributes = $this->storeExtraPhotos($attributes);

        $draft = null;

        try {
            $outcome = $creator->create($partner, $this->serviceCategory(), $attributes, $photos, $draft);
        } catch (PartnerServiceException|DraftNotPublishableException $e) {
            // Le foto le ha già cancellate il service; quelle in upload
            // restano nel componente, così l'admin corregge e ritenta.
            Flux::toast(text: $e->getMessage(), variant: 'danger');

            return;
        }

        if ($outcome === DraftCompletion::AwaitingPayout) {
            // La spec manderebbe su admin.users.show con l'avviso, ma il
            // layout del pannello non ha un canale per i messaggi flash (solo
            // flux:toast, che un redirect si porta via): l'avviso resta qui,
            // con il link alla scheda del partner. Lo disegna partner-aside.
            // Due esiti diversi per due cause diverse: una smartbox di un
            // partner che incassa fuori non aspetta un onboarding Stripe da
            // finire, aspetta che passi all'incasso online (27/09/2026).
            $this->pendingNoticeSmartbox = $this->smartboxPaymentBlock();

            $this->pendingNotice = __(
                $this->pendingNoticeSmartbox
                    ? 'admin-catalog.create.awaiting_smartbox_payment'
                    : 'admin-catalog.create.awaiting_stripe',
                ['name' => $partner->partnerProfile?->business_name ?: $partner->name],
                'it',
            );

            // Le foto sono già sulla bozza: tenerle anche qui significa
            // ricaricarle al clic successivo.
            $this->photos = [];
            $this->created = true;

            return;
        }

        // La conferma di pubblicazione: un flash di sessione sopravvive al
        // redirect, un flux:toast no. Lo legge show.blade.php (Task 9).
        session()->flash('catalog_created', __('admin-catalog.create.published', [], 'it'));

        $this->created = true;

        // `publishedRow()` torna null se una famiglia nuova non è mappata o
        // uno scope la nasconde: una pubblicazione riuscita non deve
        // diventare un 500 sul redirect, con la riga già a catalogo.
        $row = $creator->publishedRow($draft);

        $this->redirectRoute(
            $row === null ? 'admin.catalog.index' : 'admin.catalog.show',
            $row === null ? [] : ['type' => $creator->catalogType($draft), 'id' => $row->id],
            navigate: true,
        );
    }

    /** Partner scelto, con il profilo già caricato per il riepilogo. */
    protected function partner(): ?User
    {
        if ($this->partnerId === null) {
            return null;
        }

        return $this->partnerModel ??= User::query()->with('partnerProfile')->find($this->partnerId);
    }

    /**
     * Bozza fittizia richiesta da HandlesPhotoUploads. Non è mai salvata:
     * `saved` resta vuoto e `removeSaved()` non ha niente da togliere
     * (`update()` su un model inesistente non tocca il database).
     */
    protected function draft(): StructureDraft
    {
        return $this->draftStub ??= new StructureDraft;
    }

    protected function photoMinError(): string
    {
        return __('admin-catalog.create.photos_min', [], 'it');
    }

    /**
     * Nel pannello non esistono foto già salvate: la pagina non disegna
     * nessun bottone per togliersele. Il metodo del wizard resta però
     * raggiungibile dal payload e cancellerebbe dal disco `public` il
     * percorso che il client ha messo in `saved`, quindi qui non fa niente.
     */
    public function removeSaved(int $index): void {}

    /**
     * Foto della famiglia oltre a quelle della scheda (le camere della
     * struttura), da scrivere su disco solo adesso: dopo la validazione e dopo
     * le foto della scheda, prima del service, che le ripulisce se rifiuta.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function storeExtraPhotos(array $attributes): array
    {
        return $attributes;
    }

    /** Porta i tab testi sulla lingua che ha l'errore (stesso metodo di ArticleEdit). */
    protected function focusLocaleOf(array $keys): void
    {
        foreach ($keys as $key) {
            foreach (['it', 'en'] as $locale) {
                if (str_ends_with($key, '.'.$locale)) {
                    $this->lang = $locale;

                    return;
                }
            }
        }
    }

    /**
     * Azzera ciò che dipende dal partner: solo la smartbox ne ha (le
     * strutture incluse). Il nome è questo, e SmartboxCreate sovrascrive
     * esattamente questo: un `resetPartnerSelections()` sarebbe codice morto
     * e il cambio partner non azzererebbe niente.
     */
    protected function resetPartnerDependentState(): void {}

    /** Categoria scritta sulla bozza: struttura | servizi | attivita | smartbox. */
    abstract protected function serviceCategory(): string;

    /**
     * Campi della bozza. **Valida per prima cosa** (normalizzazioni comprese)
     * e poi compone l'array: è l'unico punto di validazione della pagina.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    abstract protected function draftAttributes(): array;

    private function setPartner(?int $partnerId): void
    {
        $eligible = $this->eligiblePartners();

        $this->partnerId = $partnerId !== null && $eligible->has($partnerId) ? $partnerId : null;
        $this->partnerChoice = (string) ($this->partnerId ?? '');
        $this->partnerModel = null;
    }
}
