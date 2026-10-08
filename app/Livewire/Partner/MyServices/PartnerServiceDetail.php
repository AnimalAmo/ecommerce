<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class PartnerServiceDetail extends Component
{
    /**
     * Step «Informazioni generali» del percorso Attività/Eventi (quello che
     * chiede i posti): lo stesso numero che ActivityInfo passa a saveStep().
     */
    private const ACTIVITY_INFO_STEP = 5;

    public StructureDraft $draft;

    public function mount(StructureDraft $draft): void
    {
        // Solo i propri servizi elencabili (completati, in attesa di Stripe o
        // bozze in corso, vedi StructureDraft::scopeInProgress()):
        // stessa scope della lista, così dettaglio e lista non divergono.
        abort_unless(
            StructureDraft::listableFor(Auth::id())->whereKey($draft->id)->exists(),
            403,
        );

        $this->draft = $draft;
    }

    /** Etichetta tag tipologia (Holiday / Eventi / Smartbox) per il badge. */
    public function tag(): string
    {
        return __('partner.services.tag_'.$this->draft->family());
    }

    /**
     * Righe dell'accordion (XD "Dettaglio ... – dettagli"), una per step del
     * wizard della famiglia e nello stesso ordine. Ogni riga ha `label`,
     * `text` (il paragrafo) e `details` (le righe sotto); stanze e foto hanno
     * in più un corpo loro (`rooms`, `photos`). Le chiavi-opzione salvate su
     * DB sono risolte in label localizzate; i testi liberi del partner restano
     * come inseriti.
     *
     * Difetto F7 (audit del 28/09/2026): erano dieci righe fisse, le stesse
     * per ogni famiglia e ricalcate sul percorso Struttura. Un evento non
     * mostrava tipologie, zona, prenotazione, ricorrenza, posti, date e orari,
     * e in compenso rispondeva «Non specificato» a stanze e coordinate
     * bancarie, domande che il suo wizard non fa. Il partner non poteva
     * rileggere ciò che aveva salvato senza ripercorrere gli step.
     *
     * Null-safe per costruzione: il dettaglio si apre anche sulle bozze a metà
     * wizard (W2), quindi qualunque colonna può essere ancora vuota.
     *
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return match ($this->draft->family()) {
            'attivita' => $this->activityRows(),
            'smartbox' => $this->smartboxRows(),
            default => $this->structureRows(),
        };
    }

    /**
     * Stanze per la scheda: nome (o etichetta della tipologia), tipologia, prezzo
     * e unità, anche per le righe legacy (normalizedRooms le porta al formato nuovo).
     *
     * @return list<array{name:string,type:string,price:string,units:int}>
     */
    private function roomList(): array
    {
        $locale = app()->getLocale();

        return array_map(function (array $room) use ($locale): array {
            $typeLabel = (string) ServiceOptionLabels::label('room_type', $room['type']);
            $name = trim((string) ($room['name'][$locale] ?? ''));

            if ($name === '') {
                $name = trim((string) (collect($room['name'])->first(fn ($text) => filled($text)) ?? ''));
            }

            return [
                'name' => $name !== '' ? $name : $typeLabel,
                'type' => $typeLabel,
                'price' => $room['price'],
                'units' => $room['units'],
            ];
        }, $this->draft->normalizedRooms());
    }

    /** Percorso Struttura, step 1-11. */
    private function structureRows(): array
    {
        $draft = $this->draft;

        return [
            $this->row(__('partner.services.section_type'), ServiceOptionLabels::label('type', $draft->type)),
            $this->nameRow(),
            $this->row(__('partner.services.section_location'), $this->address()),
            $this->descriptionRow(),
            [
                'label' => __('partner.services.section_rooms'),
                'rooms' => $this->roomList(),
                'checkin' => [$draft->checkin_from, $draft->checkin_to],
                'checkout' => [$draft->checkout_from, $draft->checkout_to],
                'text' => filled($draft->rooms) ? null : __('partner.services.not_provided'),
            ],
            $this->cancellationRow(),
            $this->servicesRow(),
            $this->extraRow(),
            $this->smartboxRow(),
            $this->photosRow(),
            $this->paymentRow(),
        ];
    }

    /**
     * Coordinate bancarie: quelle del profilo, che dallo step 11 è la fonte
     * unica (difetto W7, 28/09/2026). La copia sulla bozza resta indietro se il
     * partner le cambia in «Metodo di pagamento» (tester, 28/09/2026): serve
     * solo da ripiego per un profilo che non le ha ancora.
     */
    private function paymentRow(): array
    {
        $draft = $this->draft;
        $profile = $draft->user?->partnerProfile;
        $source = filled($profile?->iban) ? $profile : $draft;

        return $this->row(__('partner.services.section_payment'), $this->join([
            $source->account_holder,
            $source->iban,
            $source->bic,
        ], ' · '));
    }

    /**
     * Percorso Attività/Eventi, step 1-10: attività, servizi professionali ed
     * eventi. Niente stanze né coordinate bancarie, che questo wizard non
     * chiede; il ramo (evento o no) decide le stesse righe che decide il
     * publisher, così il partner rilegge quello che il catalogo mostrerà.
     */
    private function activityRows(): array
    {
        $draft = $this->draft;
        $isEvent = $draft->type === 'eventi';

        $rows = [
            $this->row(__('partner.services.section_service_type'), ServiceOptionLabels::label('type', $draft->type)),
            // Step 2: le tipologie a scelta multipla, col testo libero di
            // «Altro» sotto, come le mostra la scheda pubblica.
            $isEvent
                ? $this->row(
                    __('partner.activity_name.field_categories_event'),
                    $this->join(ServiceOptionLabels::labels('event_category', $draft->event_categories)),
                    [$draft->event_categories_other],
                )
                : $this->row(
                    __('partner.activity_name.field_categories'),
                    $this->join(ServiceOptionLabels::labels('activity_category', $draft->activity_categories)),
                    [$draft->activity_categories_other],
                ),
            $this->nameRow(),
            // Step 3: l'evento ha un punto d'incontro, il professionista la
            // zona in cui opera al suo posto.
            $this->row(__('partner.services.section_location'), $this->address(), [
                $isEvent
                    ? $this->labelled('partner.activity_location.meeting_point', $draft->meeting_point)
                    : $this->labelled('partner.activity_location.operating_area', $draft->operating_area),
            ]),
            $this->descriptionRow(),
        ];

        // La descrizione dettagliata la chiede solo il ramo Attività (W1), e il
        // publisher la toglie agli eventi.
        if (! $isEvent) {
            $rows[] = $this->row(__('partner.services.section_detailed_description'), $draft->detailed_description);
        }

        return [
            ...$rows,
            $this->row(__('partner.activity_info.heading'), null, $this->generalInfo($isEvent)),
            $this->servicesRow(),
            $this->extraRow(),
            $this->costRow(),
            $this->photosRow(),
            $this->cancellationRow(),
        ];
    }

    /**
     * Percorso Smartbox: le righe di sempre, meno luogo, stanze e coordinate
     * bancarie, che il suo wizard non chiede.
     */
    private function smartboxRows(): array
    {
        return [
            $this->row(__('partner.services.section_service_type'), ServiceOptionLabels::label('type', $this->draft->type)),
            $this->nameRow(),
            $this->descriptionRow(),
            $this->cancellationRow(),
            $this->servicesRow(),
            $this->extraRow(),
            $this->photosRow(),
        ];
    }

    /**
     * Step 5 «Informazioni generali»: date, orari, ricorrenza, prenotazione e
     * posti, una riga ciascuno e solo se c'è un valore.
     *
     * @return list<?string>
     */
    private function generalInfo(bool $isEvent): array
    {
        $draft = $this->draft;

        $seats = match (true) {
            $draft->max_participants !== null => (string) $draft->max_participants,
            // Vuoto vuol dire «nessun limite» (lo dice il wizard stesso) solo
            // dopo che l'evento ha risposto allo step: prima è una domanda non
            // ancora fatta. All'attività i posti non li chiede il wizard.
            $isEvent && $draft->current_step >= self::ACTIVITY_INFO_STEP => __('partner.services.max_participants_unlimited'),
            default => null,
        };

        return [
            $this->labelled('partner.activity_info.date_start', $draft->date_start?->format('d/m/Y')),
            $this->labelled('partner.activity_info.date_end', $draft->date_end?->format('d/m/Y')),
            $this->labelled('partner.activity_info.time_start', $draft->time_start),
            $this->labelled('partner.activity_info.time_end', $draft->time_end),
            // Sola etichetta, e solo degli eventi: il publisher la toglie alle attività.
            $isEvent ? ServiceOptionLabels::label('event_recurrence', $draft->recurrence) : null,
            ServiceOptionLabels::label('booking_requirement', $draft->booking_requirement),
            $this->labelled('partner.activity_info.max_participants', $seats),
        ];
    }

    /**
     * Step 9 «Smartbox» del percorso Struttura: l'adesione alle smartbox altrui
     * e le tipologie scelte.
     *
     * Difetto W6 (audit del 28/09/2026): lo step salva `smartbox_consent` e
     * `smartbox_types` e nessuno li leggeva, nemmeno questa pagina. Se
     * l'adesione debba guidare la scelta delle strutture di un cofanetto, o se
     * lo step vada tolto, è una decisione aperta con la cliente; intanto il
     * partner può almeno rileggere cosa ha dichiarato.
     */
    private function smartboxRow(): array
    {
        $draft = $this->draft;

        return $this->row(
            __('partner.services.section_smartbox'),
            ServiceOptionLabels::label('consent', $draft->smartbox_consent),
            [$draft->smartbox_consent === 'si' ? $this->join(ServiceOptionLabels::labels('smartbox_consent', $draft->smartbox_types)) : null],
        );
    }

    private function costRow(): array
    {
        $draft = $this->draft;

        return $this->row(
            __('partner.services.section_cost'),
            ServiceOptionLabels::label('price_type', $draft->price_type),
            [$draft->price_type === 'pagamento' ? $this->labelled('partner.activity_cost.price_label', filled($draft->price_per_person) ? '€'.$draft->price_per_person : null) : null],
        );
    }

    private function nameRow(): array
    {
        return $this->row(__('partner.services.section_name'), $this->draft->name);
    }

    private function descriptionRow(): array
    {
        return $this->row(__('partner.services.section_description'), $this->draft->description);
    }

    private function cancellationRow(): array
    {
        $when = $this->draft->cancellation_when;

        return $this->row(__('partner.services.section_cancellation'), match (true) {
            $when === '1' => __('partner.services.cancellation_day'),
            filled($when) => __('partner.services.cancellation_days', ['days' => $when]),
            default => null,
        });
    }

    private function servicesRow(): array
    {
        $draft = $this->draft;

        return $this->row(__('partner.services.section_services'), $this->join([
            ...ServiceOptionLabels::labels('services', $draft->services),
            ...ServiceOptionLabels::labels('additional', $draft->additional_services),
            $draft->additional_other,
            ...ServiceOptionLabels::labels('rules', $draft->rules),
        ]));
    }

    private function extraRow(): array
    {
        $draft = $this->draft;

        return $this->row(__('partner.services.section_extra'), $this->join([
            ...ServiceOptionLabels::labels('animal_services', $draft->animal_services),
            $draft->animal_services_other,
        ]));
    }

    private function photosRow(): array
    {
        $photos = $this->draft->photos ?? [];

        return [
            'label' => __('partner.services.section_photos'),
            'photos' => array_map(fn ($path) => Storage::disk('public')->url($path), $photos),
            'text' => filled($photos) ? null : __('partner.services.not_provided'),
        ];
    }

    /** Indirizzo · città (provincia) · CAP · CIR, saltando i vuoti. */
    private function address(): string
    {
        $draft = $this->draft;

        return $this->join([
            $draft->address,
            trim($draft->city.' '.($draft->province ? "({$draft->province})" : '')),
            $draft->zip,
            $draft->license,
        ], ' · ');
    }

    /**
     * Una riga dell'accordion. Senza testo né dettagli dice «Non specificato»:
     * una riga vuota sembrerebbe un errore di pagina, non un campo saltato.
     *
     * @param  list<?string>  $details
     */
    private function row(string $label, ?string $text, array $details = []): array
    {
        $details = array_values(array_filter($details, filled(...)));

        return [
            'label' => $label,
            'text' => filled($text) ? $text : ($details === [] ? __('partner.services.not_provided') : null),
            'details' => $details,
        ];
    }

    /** «Etichetta: valore», o null se il valore manca. */
    private function labelled(string $labelKey, ?string $value): ?string
    {
        return filled($value) ? __($labelKey).': '.$value : null;
    }

    /** @param  list<?string>  $values */
    private function join(array $values, string $glue = ', '): string
    {
        return implode($glue, array_filter($values, filled(...)));
    }

    public function render()
    {
        return view('livewire.partner.my-services.detail', [
            'tag' => $this->tag(),
            'rows' => $this->rows(),
        ])->title(__('partner.services.detail_title'));
    }
}
