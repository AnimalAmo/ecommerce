<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\CreatesPartnerService;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\HotelLocationForm;
use App\Livewire\Forms\HotelPaymentForm;
use App\Livewire\Forms\HotelRoomsForm;
use App\Livewire\Forms\HotelServicesForm;
use App\Models\Region\Province;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pannello — nuova scheda della famiglia struttura, creata dal superadmin per
 * conto di un partner (spec §5.3). Gli undici step del percorso partner stanno
 * qui su una pagina sola: niente bozza intermedia, niente sessione, si valida
 * tutto e si pubblica in un colpo solo.
 *
 * `service_category` arriva dall'indirizzo (`structure` | `service`) e resta
 * modificabile: le due categorie condividono gli stessi campi e lo stesso
 * publisher — 'servizi' oggi va a catalogo come Structure, a notte, esattamente
 * come fa il wizard (StructurePublisher::publish). Cambiarlo è una decisione
 * della cliente, non di questa pagina.
 *
 * I quattro Form object sono quelli del wizard, riusati come sono: il partner
 * deve poter riaprire la scheda dal suo percorso e risalvarla senza trovarsi
 * respinto da regole che lui non ha.
 */
class StructureCreate extends Component
{
    use CreatesPartnerService, ProvidesTimeSlots;

    /** Famiglia dell'indirizzo: structure | service. Decide la categoria iniziale. */
    #[Locked]
    public string $family = 'structure';

    /** Categoria salvata sulla bozza: struttura | servizi. */
    public string $category = 'struttura';

    /** Sotto-tipologia: hotel | bb | agriturismo | casa_vacanza. */
    public string $type = '';

    /** @var array<string, string> */
    public array $name = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['it' => '', 'en' => ''];

    /** Giorni prima dell'arrivo entro cui si cancella gratis: 30 | 15 | 7 | 1. */
    public string $cancellationWhen = '1';

    /** @var list<string> */
    public array $animalServices = [];

    /** @var array<string, string> */
    public array $animalServicesOther = ['it' => '', 'en' => ''];

    /** Consenso all'inserimento nelle smartbox altrui: si | no. */
    public string $smartboxConsent = 'si';

    /**
     * Adesioni alla smartbox (gruppo `smartbox_consent`).
     *
     * @var list<string>
     */
    public array $smartboxTypes = [];

    public HotelLocationForm $location;

    public HotelRoomsForm $rooms;

    public HotelServicesForm $services;

    public HotelPaymentForm $payment;

    public function mount(string $family): void
    {
        $this->family = $family;
        $this->category = $family === 'service' ? 'servizi' : 'struttura';
    }

    /**
     * Casa vacanza: lo step camere diventa "alloggio intero" (una riga sola,
     * `count` bloccato a 1, posti letto al posto della tipologia).
     * HotelRoomsForm ricava `wholeProperty` solo in setFromDraft(), che qui non
     * gira mai: lo allinea il componente. Le righe si azzerano perché le regole
     * `size:1` e `in:1` rifiuterebbero quelle già scritte mentre la vista non le
     * mostra più — il partner resterebbe con un errore che non può correggere
     * (è il difetto latente del wizard, mappa §HotelRooms).
     */
    public function updatedType(): void
    {
        $whole = $this->type === 'casa_vacanza';

        if ($whole === $this->rooms->wholeProperty) {
            return;
        }

        $this->rooms->wholeProperty = $whole;
        $this->rooms->rooms = [$this->rooms->blankRow()];
        $this->resetErrorBag('rooms.rooms.*');
    }

    /** Senza consenso le adesioni non hanno senso: si svuotano invece di restare appese. */
    public function updatedSmartboxConsent(): void
    {
        if ($this->smartboxConsent === 'no') {
            $this->smartboxTypes = [];
        }
    }

    public function addRoom(): void
    {
        if ($this->rooms->wholeProperty) {
            return;
        }

        $this->rooms->rooms[] = $this->rooms->blankRow();
    }

    public function incrementRoom(int $index): void
    {
        if (! $this->rooms->wholeProperty && isset($this->rooms->rooms[$index])) {
            $this->rooms->rooms[$index]['count']++;
        }
    }

    public function decrementRoom(int $index): void
    {
        if (! $this->rooms->wholeProperty && isset($this->rooms->rooms[$index]) && $this->rooms->rooms[$index]['count'] > 0) {
            $this->rooms->rooms[$index]['count']--;
        }
    }

    /**
     * Rimuove una riga camera. Il wizard non ce l'ha: chi sbaglia riga deve
     * riscriverla. Resta sempre almeno una riga, perché `rooms` non può essere
     * vuoto (regola `min:1`) e senza righe la scheda non è pubblicabile.
     */
    public function removeRoom(int $index): void
    {
        if ($this->rooms->wholeProperty || count($this->rooms->rooms) <= 1 || ! isset($this->rooms->rooms[$index])) {
            return;
        }

        unset($this->rooms->rooms[$index]);
        $this->rooms->rooms = array_values($this->rooms->rooms);
        $this->resetErrorBag('rooms.rooms.*');
    }

    public function render()
    {
        return view('livewire.admin.catalog.structure-create', [
            ...$this->partnerViewData(),
            'sub' => __('admin-catalog.create.structure.sub_'.$this->category),
            'provinces' => Province::query()->orderBy('name')->get(),
            'times' => $this->times(),
            // slug => etichetta: la vista NON ricompone la label a mano, così
            // una quinta finestra di cancellazione compare in tutte e tre le
            // famiglie insieme alla whitelist.
            'cancellationOptions' => ServiceOptionLabels::options('cancellation'),
            'options' => [
                'type' => ServiceOptionLabels::options('structure_type'),
                'room' => ServiceOptionLabels::options('room_type'),
                'services' => ServiceOptionLabels::options('services'),
                'additional' => ServiceOptionLabels::options('additional'),
                'rules' => ServiceOptionLabels::options('rules'),
                'animal' => ServiceOptionLabels::options('animal_services'),
                // Le quattro adesioni: gruppo `smartbox_consent`, non `smartbox_types`.
                'smartbox' => ServiceOptionLabels::options('smartbox_consent'),
            ],
        ])
            ->layout('layouts::admin')
            ->title(__('admin-catalog.create.structure.title'));
    }

    /** Categoria richiesta da AdminServiceCreator (colonna `service_category`). */
    protected function serviceCategory(): string
    {
        return $this->category;
    }

    protected function photoDirectory(): string
    {
        return 'structure-photos';
    }

    // NIENTE override di photoMinError(): il testo è quello del trait
    // (`admin-catalog.create.photos_min`). Una seconda formulazione dello
    // stesso errore — «Devi caricare almeno 4 foto.» di partner.* — farebbe
    // parlare la stessa regola in due modi nello stesso pannello.

    /**
     * Un solo validate() per tutta la pagina: gli errori di tutte le sezioni
     * compaiono insieme, non uno step alla volta. Le regole dei Form object si
     * prefissano a mano con il nome della proprietà — lo stesso prefisso che
     * Livewire mette quando è il Form a validarsi — così le chiavi d'errore
     * sono quelle che la vista cita nei `flux:error`.
     *
     * Regole esplicite e non globali: con quelle globali Livewire validerebbe
     * da sé ogni Form object, compreso HotelPaymentForm, che richiede tutti e
     * tre i campi. Qui il pagamento è facoltativo come nel wizard.
     *
     * L'ORDINE DI QUESTO METODO È PORTANTE: prima le chiavi che non hanno un
     * corrispettivo nei Form object, poi il merge delle regole dei Form, poi —
     * in coda — i rafforzamenti, che si ACCODANO alle regole appena fuse. Le
     * cinque chiavi rafforzate (`location.province`, i tre gruppi di
     * `services`, `rooms.rooms.*.type`) sono tutte dichiarate anche dai Form:
     * scriverle prima del merge le farebbe cancellare dal merge stesso, in
     * silenzio, e l'admin potrebbe salvare la provincia «ZZ» e slug inventati.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            // Regole degli step, copiate alla lettera dal wizard: il partner
            // deve poter risalvare la stessa scheda dal suo percorso.
            'category' => ['required', 'string', 'in:struttura,servizi'],
            'type' => ['required', 'string', Rule::in(ServiceOptionLabels::slugs('structure_type'))],
            // Rafforzamento 2 del contratto: 110 caratteri per lingua (il
            // wizard ne ammette 128). Nome IT + EN vivono nello stesso JSON di
            // `structures.name`, che è un varchar(255). Descrizione e "altro"
            // restano ai 200 del wizard: stringere anche loro bloccherebbe il
            // partner su testi che ha già scritto.
            'name.it' => ['required', 'string', 'max:110'],
            'name.en' => ['nullable', 'string', 'max:110'],
            'description.it' => ['required', 'string', 'max:200'],
            'description.en' => ['nullable', 'string', 'max:200'],
            'cancellationWhen' => ['required', Rule::in(ServiceOptionLabels::slugs('cancellation'))],
            'animalServices' => ['array'],
            'animalServices.*' => ['string', Rule::in(ServiceOptionLabels::slugs('animal_services'))],
            'animalServicesOther.it' => ['nullable', 'string', 'max:200'],
            'animalServicesOther.en' => ['nullable', 'string', 'max:200'],
            'smartboxConsent' => ['required', Rule::in(ServiceOptionLabels::slugs('consent'))],
            'smartboxTypes' => ['array'],
            'smartboxTypes.*' => ['string', Rule::in(ServiceOptionLabels::slugs('smartbox_consent'))],
            'photos' => ['array'],
            'photos.*' => ['image', 'max:8192'],
        ];

        // 1. Le regole dei Form object, con il prefisso della proprietà — lo
        //    stesso che Livewire userebbe se fosse il Form a validarsi.
        foreach ($this->sectionForms() as $property => $form) {
            foreach ($form->rules() as $key => $rule) {
                $rules[$property.'.'.$key] = $rule;
            }
        }

        // 2. E SOLO ADESSO i rafforzamenti, accodati alle regole appena fuse.
        //    Non si assegna: si accoda. `location.province` deve restare
        //    `required|string|max:64` del Form **più** l'exists, non l'uno al
        //    posto dell'altro.

        // Rafforzamento 1: la sigla deve esistere davvero. Oggi una sigla
        // sbagliata dà region_id NULL in silenzio (StructurePublisher) e la
        // scheda sparisce da ogni elenco regionale.
        $rules['location.province'][] = Rule::exists('provinces', 'short_name');

        // Rafforzamento 3: le liste di slug del wizard non hanno whitelist.
        // SEMPRE slugs(), mai options(): options() torna slug => etichetta, e
        // Rule::in ne itera i VALORI — accetterebbe 'Hotel' e rifiuterebbe
        // 'hotel'. I rafforzamenti stanno qui e non nei Form object perché
        // quelli sono condivisi col wizard: una bozza vecchia con uno slug
        // fuori elenco deve restare salvabile dal partner.
        $rules['services.services.*'][] = Rule::in(ServiceOptionLabels::slugs('services'));
        $rules['services.additional.*'][] = Rule::in(ServiceOptionLabels::slugs('additional'));
        $rules['services.structureRules.*'][] = Rule::in(ServiceOptionLabels::slugs('rules'));
        $rules['rooms.rooms.*.type'][] = Rule::in($this->roomTypes());

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [
            'category.required' => __('partner.create_service.error_required'),
            'category.in' => __('partner.create_service.error_required'),
            'type.required' => __('partner.structure_type.error_required'),
            'type.in' => __('partner.structure_type.error_required'),
            'name.it.required' => __('partner.hotel_title.error_required'),
            'name.it.max' => __('admin-catalog.create.validation.name_max'),
            'name.en.max' => __('admin-catalog.create.validation.name_max'),
            'description.it.required' => __('partner.hotel_description.error_required'),
            'description.it.max' => __('admin-catalog.create.validation.text_max'),
            'description.en.max' => __('admin-catalog.create.validation.text_max'),
            'cancellationWhen.required' => __('partner.hotel_cancellation.error_required'),
            'cancellationWhen.in' => __('partner.hotel_cancellation.error_required'),
            'photos.*.image' => __('admin-catalog.create.validation.photo_image'),
            'photos.*.max' => __('admin-catalog.create.validation.photo_max'),
            'location.province.exists' => __('admin-catalog.create.validation.province_exists'),
            'services.services.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'services.additional.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'services.structureRules.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'animalServices.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'smartboxTypes.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'rooms.rooms.*.type.in' => __('admin-catalog.create.validation.room_type_unknown'),
        ];

        foreach ($this->sectionForms() as $property => $form) {
            // `Livewire\Form` NON dichiara `messages()` nella classe base (si
            // veda `Features/SupportFormObjects/Form.php`: c'è solo il
            // `getMessages()` protetto di HandlesValidation, che cerca il
            // metodo con method_exists). Dei quattro Form di questa pagina solo
            // HotelRoomsForm ce l'ha: senza questo controllo la riga sotto
            // darebbe «Call to undefined method …HotelLocationForm::messages()»
            // e la pagina morirebbe al primo salvataggio.
            if (! method_exists($form, 'messages')) {
                continue;
            }

            foreach ($form->messages() as $key => $message) {
                $messages[$property.'.'.$key] = $message;
            }
        }

        return $messages;
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        $prefix = 'admin-catalog.create.structure.';

        return [
            'type' => __($prefix.'type'),
            'name.it' => __($prefix.'name'),
            'name.en' => __($prefix.'name'),
            'description.it' => __($prefix.'description'),
            'description.en' => __($prefix.'description'),
            'location.address' => __($prefix.'address'),
            'location.city' => __($prefix.'city'),
            'location.province' => __($prefix.'province'),
            'location.zip' => __($prefix.'zip'),
            'location.license' => __($prefix.'license'),
            'rooms.checkinFrom' => __($prefix.'checkin'),
            'rooms.checkinTo' => __($prefix.'checkin'),
            'rooms.checkoutFrom' => __($prefix.'checkout'),
            'rooms.checkoutTo' => __($prefix.'checkout'),
            'payment.accountHolder' => __($prefix.'account_holder'),
            'payment.iban' => __($prefix.'iban'),
            'payment.bic' => __($prefix.'bic'),
        ];
    }

    /**
     * Valida e compone gli attributi colonna della bozza.
     *
     * La validazione è QUI, come prima riga: il trait chiama questo metodo
     * prima di `collectPhotos()`, quindi finché non passa non finisce niente
     * su disco (spec §5.3). Non esiste nessun `validateSections()`.
     *
     * Le traduzioni passano da array_filter come nel wizard: un EN vuoto non
     * si salva e scatta il fallback sull'IT.
     *
     * @return array<string, mixed>
     */
    protected function draftAttributes(): array
    {
        $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        $filled = fn (array $values): array => array_filter($values, fn ($value) => filled($value));

        return [
            'type' => $this->type,
            'name' => $filled($this->name),
            'description' => $filled($this->description),
            'cancellation_when' => $this->cancellationWhen,
            'animal_services' => $this->animalServices,
            'animal_services_other' => $filled($this->animalServicesOther),
            'smartbox_consent' => $this->smartboxConsent,
            'smartbox_types' => $this->smartboxConsent === 'si' ? $this->smartboxTypes : [],
            ...$this->location->toDraft(),
            ...$this->rooms->toDraft(),
            ...$this->services->toDraft(),
            // Sezione facoltativa: vuota non si scrive, così le colonne restano
            // NULL come dopo lo "Inserisci più tardi" del wizard.
            ...($this->paymentFilled() ? $this->payment->toDraft() : []),
        ];
    }

    /** Form object le cui regole entrano nel validate unico (il pagamento solo se compilato). */
    private function sectionForms(): array
    {
        $forms = [
            'location' => $this->location,
            'rooms' => $this->rooms,
            'services' => $this->services,
        ];

        return $this->paymentFilled() ? [...$forms, 'payment' => $this->payment] : $forms;
    }

    /** O tutte e tre le coordinate, o nessuna: una sola compilata è un errore da segnalare. */
    private function paymentFilled(): bool
    {
        return filled($this->payment->accountHolder) || filled($this->payment->iban) || filled($this->payment->bic);
    }

    /**
     * Tipologie ammesse per le righe camera: la casa vacanza ne ha una sola,
     * fittizia, e sta nel gruppo `room_type_whole` del Task 1 — scriverla a
     * mano qui renderebbe quel gruppo un elenco che nessuno legge.
     *
     * @return list<string>
     */
    private function roomTypes(): array
    {
        return ServiceOptionLabels::slugs($this->rooms->wholeProperty ? 'room_type_whole' : 'room_type');
    }
}
