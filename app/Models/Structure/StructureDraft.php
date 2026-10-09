<?php

namespace App\Models\Structure;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Translatable\HasTranslations;

/**
 * Bozza di onboarding di un servizio partner (wizard multi-step: hotel 11,
 * attività/eventi 10 step ma chiusura a 11, smartbox 12). Resta `draft` finché
 * l'utente non completa l'ultimo step del flusso, così uno stato parziale è
 * sempre salvato se interrompe. `publish_requested_at` valorizzato = il partner
 * l'ha chiusa quando non poteva ancora essere pagato: la pubblica
 * AwaitingDraftPublisher appena Stripe è collegato (P4).
 */
class StructureDraft extends Model
{
    use HasTranslations;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Primo step oltre il quale una bozza è "iniziata davvero" e compare in
     * "I miei servizi" come bozza in corso. Lo step 2 è quello del nome in
     * tutte e tre le famiglie (HotelTitle, ActivityName, SmartboxName) e il
     * nome è obbligatorio lì: è il primo dato scritto dal partner, ed è anche
     * il primo requisito di pubblicazione (DraftPublisher::isPublishable).
     * Sotto restano le bozze che portano solo la card e la tipologia: ogni
     * visita a "Crea servizio" ne apre una allo step 0, e le card «Servizio
     * professionale» ed «Evento» la portano subito allo step 1. Elencarle
     * riempirebbe la lista di righe senza nome.
     */
    public const STARTED_STEP = 2;

    /** Step di chiusura di struttura e attività (vedi finalStep()). */
    private const FINAL_STEP = 11;

    /** Step di chiusura della smartbox, che ha una sezione in più. */
    private const SMARTBOX_FINAL_STEP = 12;

    /**
     * Rotta di ogni step del wizard, per famiglia, indicizzata col numero che
     * quello step scrive in `current_step` (saveStep). Gli step sono in fila e
     * nessuno è condizionale: lo step da riprendere è sempre `current_step + 1`.
     * Le attività finiscono a 10 perché la chiusura a 11 la scrive
     * DraftCompleter, non uno step.
     */
    private const WIZARD_ROUTES = [
        'struttura' => [
            1 => 'partner.structure.type',
            2 => 'partner.structure.hotel.title',
            3 => 'partner.structure.hotel.location',
            4 => 'partner.structure.hotel.description',
            5 => 'partner.structure.hotel.rooms',
            6 => 'partner.structure.hotel.cancellation',
            7 => 'partner.structure.hotel.services',
            8 => 'partner.structure.hotel.animal-services',
            9 => 'partner.structure.hotel.smartbox',
            10 => 'partner.structure.hotel.photos',
            11 => 'partner.structure.hotel.payment',
        ],
        'attivita' => [
            1 => 'partner.activity.type',
            2 => 'partner.activity.name',
            3 => 'partner.activity.location',
            4 => 'partner.activity.description',
            5 => 'partner.activity.info',
            6 => 'partner.activity.included',
            7 => 'partner.activity.animal-services',
            8 => 'partner.activity.cost',
            9 => 'partner.activity.photos',
            10 => 'partner.activity.cancellation',
        ],
        'smartbox' => [
            1 => 'partner.smartbox.type',
            2 => 'partner.smartbox.name',
            3 => 'partner.smartbox.description',
            4 => 'partner.smartbox.duration',
            5 => 'partner.smartbox.cancellation',
            6 => 'partner.smartbox.meals',
            7 => 'partner.smartbox.offers',
            8 => 'partner.smartbox.included',
            9 => 'partner.smartbox.included-animals',
            10 => 'partner.smartbox.structures',
            11 => 'partner.smartbox.photos',
            12 => 'partner.smartbox.price',
        ],
    ];

    /** Le card di StructureType: le quattro tipologie di struttura ricettiva. */
    public const STRUCTURE_TYPES = ['hotel', 'bb', 'agriturismo', 'casa_vacanza'];

    /** Le card di ActivityType: servizio professionale ed evento. */
    public const ACTIVITY_TYPES = ['attivita', 'eventi'];

    /**
     * Colonne che non tutti i rami del wizard riempiono, ciascuna coi rami in
     * cui resta valida. I rami sono tre: `struttura` (una delle
     * STRUCTURE_TYPES), `attivita` (servizio professionale) ed `eventi`. Le
     * colonne che qui non compaiono sono comuni a tutti (nome, luogo,
     * descrizione breve, servizi, foto, prezzo, cancellazione) e un cambio di
     * ramo non le tocca. Chi aggiunge una colonna a un ramo solo la scrive
     * qui, e i due step del tipo la azzerano insieme (attributesForType).
     *
     * Difetto F5 dell'audit dei flussi (28/09/2026): la lista stava scritta
     * due volte, in StructureType e in ActivityType, e solo la seconda era
     * stata estesa alle colonne del 26-27/09. Passando da Evento a hotel
     * restavano posti, ricorrenza, tipologie di evento e zona operativa.
     *
     * Le scelte che non si vedono dai nomi:
     *   - le categorie professionali, la descrizione dettagliata, le date e la
     *     prenotazione valgono per entrambi i rami della famiglia attività:
     *     le categorie sono l'identità del professionista (EventPublisher già
     *     non le porta sugli eventi veri), la cliente chiede la prenotazione a
     *     tutti e due, e le date le può avere anche un'attività;
     *   - `max_participants` solo agli eventi: il wizard lo chiede lì, ed
     *     EventPublisher lo copia senza guardare il tipo, quindi un residuo
     *     farebbe esaurire un servizio professionale.
     */
    private const BRANCH_COLUMNS = [
        // Struttura ricettiva: licenza (step 3), stanze e orari (step 5),
        // adesione alle smartbox (step 9).
        'license' => ['struttura'],
        'rooms' => ['struttura'],
        'checkin_from' => ['struttura'],
        'checkin_to' => ['struttura'],
        'checkout_from' => ['struttura'],
        'checkout_to' => ['struttura'],
        'smartbox_consent' => ['struttura'],
        'smartbox_types' => ['struttura'],
        // I due rami della famiglia attività.
        'activity_categories' => ['attivita', 'eventi'],
        'activity_categories_other' => ['attivita', 'eventi'],
        'detailed_description' => ['attivita', 'eventi'],
        'date_start' => ['attivita', 'eventi'],
        'date_end' => ['attivita', 'eventi'],
        'booking_requirement' => ['attivita', 'eventi'],
        // Servizio professionale: lavora su un territorio, non ha un ritrovo.
        'operating_area' => ['attivita'],
        // Evento.
        'meeting_point' => ['eventi'],
        'time_start' => ['eventi'],
        'time_end' => ['eventi'],
        'recurrence' => ['eventi'],
        'max_participants' => ['eventi'],
        'event_categories' => ['eventi'],
        'event_categories_other' => ['eventi'],
    ];

    /**
     * Testi liberi del partner, localizzati it/en (spatie/laravel-translatable,
     * JSON in colonna). Un valore stringa assegnato finisce sul locale corrente,
     * quindi gli step non ancora convertiti ai tab lingua restano compatibili.
     */
    public array $translatable = [
        'name',
        'description',
        'detailed_description',
        'meeting_point',
        'additional_other',
        'animal_services_other',
        'activity_categories_other',
        // Testi liberi nati dalle risposte della cliente del 27/09/2026. Vanno
        // qui e non nei cast: spatie legge '' e non null quando la lingua
        // manca, quindi un `=== null` a valle non vedrebbe il vuoto — si
        // controlla con blank()/filled(), come per le sorelle qui sopra.
        'operating_area',
        'event_categories_other',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'publish_requested_at',
        'current_step',
        'service_category',
        'type',
        'name',
        'address',
        'city',
        'province',
        'zip',
        'license',
        'meeting_point',
        'description',
        'detailed_description',
        'date_start',
        'date_end',
        'time_start',
        'time_end',
        'price_type',
        'price_per_person',
        'price',
        'duration_days',
        'max_participants',
        'rooms',
        'checkin_from',
        'checkin_to',
        'checkout_from',
        'checkout_to',
        'cancellation_when',
        'services',
        'additional_services',
        'additional_other',
        'included_services',
        'meal_times',
        'meals',
        'dietary_restrictions',
        'rules',
        'animal_services',
        'animal_services_other',
        'activity_categories',
        'activity_categories_other',
        'operating_area',
        'event_categories',
        'event_categories_other',
        'recurrence',
        'booking_requirement',
        'smartbox_consent',
        'smartbox_types',
        'smartbox_structures',
        'photos',
        'account_holder',
        'iban',
        'bic',
    ];

    protected function casts(): array
    {
        return [
            'publish_requested_at' => 'datetime',
            'current_step' => 'integer',
            'duration_days' => 'integer',
            // Capienza: intero esplicito perché entra nell'aritmetica della
            // disponibilità appena il publisher la copia su events (27/09/2026).
            'max_participants' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
            'rooms' => 'array',
            'meal_times' => 'array',
            'meals' => 'array',
            'dietary_restrictions' => 'array',
            'services' => 'array',
            'additional_services' => 'array',
            'included_services' => 'array',
            'rules' => 'array',
            'animal_services' => 'array',
            'activity_categories' => 'array',
            // Tipologie di evento, scelta multipla (cliente, 27/09/2026).
            'event_categories' => 'array',
            'smartbox_types' => 'array',
            'smartbox_structures' => 'array',
            'photos' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le righe di `rooms` nel formato unico che il publisher e il wizard leggono.
     *
     * `rooms` resta la fonte di verità. Le righe legacy ({type,count,price}, o
     * {type:'intera_struttura',count:1,beds,price}) non hanno chiave: qui ne
     * ricevono una e la bozza la salva, così la stanza pubblicata (draft_key)
     * non cambia id a ogni lettura. Il resto della riga legacy non si tocca.
     *
     * @return list<array{key:string,type:string,name:array,description:array,price:string,max_guests:int,max_animals:int,units:int,photos:list<string>,amenities:list<string>}>
     */
    public function normalizedRooms(): array
    {
        $raw = array_values($this->rooms ?? []);
        $changed = false;
        $normalized = [];

        foreach ($raw as $index => $row) {
            if (blank($row['key'] ?? null)) {
                $raw[$index]['key'] = $row['key'] = (string) Str::uuid();
                $changed = true;
            }

            $type = (string) ($row['type'] ?? '');

            $normalized[] = [
                'key' => (string) $row['key'],
                'type' => $type,
                'name' => self::localized($row['name'] ?? null),
                'description' => self::localized($row['description'] ?? null),
                'price' => (string) ($row['price'] ?? ''),
                'max_guests' => (int) ($row['max_guests'] ?? self::legacyMaxGuests($type, $row)),
                'max_animals' => (int) ($row['max_animals'] ?? 2),
                'units' => max(1, (int) ($row['units'] ?? $row['count'] ?? 1)),
                'photos' => array_values($row['photos'] ?? []),
                'amenities' => array_values($row['amenities'] ?? []),
            ];
        }

        if ($changed && $this->exists) {
            $this->rooms = $raw;
            $this->saveQuietly();
        }

        return $normalized;
    }

    /** Ospiti massimi di una riga legacy, che non li chiedeva: li dice la tipologia. */
    private static function legacyMaxGuests(string $type, array $row): int
    {
        return match ($type) {
            'singola' => 1,
            'doppia' => 2,
            'tripla' => 3,
            'suite' => 4,
            'intera_struttura' => max(1, (int) ($row['beds'] ?? 1)),
            default => 2,
        };
    }

    /** Testo per lingua; una stringa nuda (mai scritta dal wizard) vale per l'italiano. */
    private static function localized(mixed $value): array
    {
        return match (true) {
            is_array($value) => $value,
            filled($value) => ['it' => (string) $value],
            default => [],
        };
    }

    /** Bozze chiuse dal partner e ferme in attesa che possa pubblicare (P4). */
    public function scopeAwaitingPublication(Builder $query): Builder
    {
        return $query->whereNotNull('publish_requested_at');
    }

    /**
     * Ciò che "I miei servizi" mostra, apre, modifica ed elimina: i servizi
     * completati, quelli in attesa di Stripe e le bozze in corso. Una sola
     * scope per lista, modifica, eliminazione e dettaglio, così i quattro punti
     * non divergono.
     *
     * Le bozze in corso (difetto W2 dell'audit del 28/09/2026) prima non
     * c'erano: una bozza scollegata dalla sessione — dall'«Indietro» verso le
     * card, da una sessione scaduta, da un upload respinto con 419 — restava a
     * database con nome, categorie e indirizzo e nessuna schermata partner la
     * poteva più riaprire. Un partner vero ci ha rinunciato al quinto
     * tentativo (Agriturismo Metina, 27/09/2026).
     */
    public function scopeListableFor(Builder $query, int $userId): Builder
    {
        // Le bozze assorbite da un'altra (comando di unione delle strutture)
        // non sono più un servizio a sé: le mostra solo quella che le ha assorbite.
        return $query->where('user_id', $userId)
            ->whereNull('merged_into_draft_id')
            ->where(fn (Builder $query): Builder => $query
                ->where('status', self::STATUS_COMPLETED)
                ->orWhereNotNull('publish_requested_at')
                ->orWhere(fn (Builder $query): Builder => $query->inProgress()))
            ->latest();
    }

    /**
     * Bozze a metà wizard: `draft`, senza segnale, oltre lo step del nome
     * (STARTED_STEP) e non ancora allo step di chiusura della famiglia.
     *
     * Lo step di chiusura resta fuori di proposito. Una bozza `draft` ferma lì
     * senza segnale non è un wizard in corso ma una chiusura tentata e
     * annullata: struttura e smartbox salvano lo step di chiusura PRIMA di
     * DraftCompleter, che se rifiuta la pubblicazione non lo toglie; oppure è
     * una bozza di prima della migrazione del segnale. È il territorio di
     * `animalamo:stuck-drafts`: se ha i dati minimi sta nel primo gruppo, e
     * `--fix` le dà il segnale.
     *
     * Stessa regola di isInProgress(), qui in SQL: se ne cambia una va cambiata
     * l'altra, o la lista e il badge divergono.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT)
            ->whereNull('publish_requested_at')
            ->where('current_step', '>=', self::STARTED_STEP)
            ->whereRaw(
                'current_step < case when service_category = ? then ? else ? end',
                ['smartbox', self::SMARTBOX_FINAL_STEP, self::FINAL_STEP],
            );
    }

    public function isAwaitingPublication(): bool
    {
        return $this->publish_requested_at !== null;
    }

    /** Bozza a metà wizard, da riprendere: stessa regola di scopeInProgress(). */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && ! $this->isAwaitingPublication()
            && $this->current_step >= self::STARTED_STEP
            && $this->current_step < $this->finalStep();
    }

    /**
     * Step con cui il wizard chiude la famiglia. Le attività hanno 10 step ma
     * da sempre si chiudono a 11 (default di completeDraft, verificato da
     * PartnerActivityCancellationTest): si resta su 11 perché è ciò che è già
     * scritto nelle bozze esistenti.
     */
    public function finalStep(): int
    {
        return match ($this->family()) {
            'smartbox' => self::SMARTBOX_FINAL_STEP,
            default => self::FINAL_STEP,
        };
    }

    /**
     * Rotta dello step `$step` del wizard della famiglia, riportata dentro
     * l'intervallo degli step esistenti.
     */
    public function wizardRoute(int $step): string
    {
        $routes = self::WIZARD_ROUTES[$this->family()];

        return $routes[min(max($step, 1), array_key_last($routes))];
    }

    /**
     * Dove riprende il partner: il primo step che non ha ancora salvato.
     * `current_step` non torna mai indietro (saveStep), quindi è lo step più
     * avanzato raggiunto, anche se nel frattempo il partner è tornato sui
     * precedenti. Per le card «Servizio professionale» ed «Evento», che
     * salvano subito lo step 1, si riparte dal nome e non dalla scelta
     * Attività/Evento che il funnel ha saltato.
     */
    public function resumeRoute(): string
    {
        return $this->wizardRoute($this->current_step + 1);
    }

    /** URL pubblico della prima foto caricata, o null. */
    public function coverPhotoUrl(): ?string
    {
        $first = $this->photos[0] ?? null;

        return $first ? Storage::disk('public')->url($first) : null;
    }

    /** Etichetta luogo per le card ("Città (PROV), Italia"), con fallback. */
    public function locationLabel(): string
    {
        $place = trim($this->city.' '.($this->province ? "({$this->province})" : ''));

        return $place !== '' ? $place.', Italia' : (string) $this->address;
    }

    /** Chiave famiglia servizio: struttura | attivita | smartbox (fallback su service_category). */
    public function family(): string
    {
        return self::familyOf($this->service_category);
    }

    /**
     * Famiglia di una `service_category`, anche prima che una bozza la porti:
     * serve a "Crea servizio" per confrontare la card scelta con la bozza
     * ripresa. Le bozze storiche con 'servizi' sono state compilate col
     * percorso hotel, quindi restano strutture.
     */
    public static function familyOf(?string $serviceCategory): string
    {
        return match ($serviceCategory) {
            'attivita' => 'attivita',
            'smartbox' => 'smartbox',
            default => 'struttura',
        };
    }

    /**
     * Gli attributi che lo step del tipo (StructureType, ActivityType) salva
     * quando il partner sceglie la tipologia `$type`. Una regola sola per i due
     * step, al posto delle due liste che divergevano (difetto F5, 28/09/2026):
     *
     *   - `type`;
     *   - `service_category`, quando la famiglia della bozza non è quella del
     *     tipo scelto. È lei che decide il publisher: prima StructureType
     *     riscriveva solo `type`, `family()` restava 'attivita' e un evento
     *     diventato hotel usciva da EventPublisher come Attività, esauribile
     *     coi posti dell'evento abbandonato. Le bozze storiche con 'servizi'
     *     sono già strutture (familyOf) e restano come sono;
     *   - le colonne del ramo abbandonato che il ramo scelto non usa
     *     (BRANCH_COLUMNS), solo se la scelta cambia ramo. Confermare la stessa
     *     tipologia, o passare da hotel a B&B, non cancella il lavoro fatto. Un
     *     tipo vuoto o di un altro wizard non ha ramo: conta come cambio.
     *
     * Un testo tradotto si svuota in tutte le lingue con `[]`: un NULL, per
     * spatie, toglierebbe solo la lingua corrente e l'inglese resterebbe lì.
     * Si azzera solo ciò che c'è, così una bozza appena nata resta com'è.
     *
     * @return array<string, mixed>
     */
    public function attributesForType(string $type): array
    {
        $branch = self::branchOf($type)
            ?? throw new InvalidArgumentException("Tipologia di bozza sconosciuta: {$type}");
        $family = $branch === 'struttura' ? 'struttura' : 'attivita';

        $attributes = ['type' => $type];

        if ($this->service_category === null || $this->family() !== $family) {
            $attributes['service_category'] = $family;
        }

        if ($this->type !== null && self::branchOf($this->type) === $branch) {
            return $attributes;
        }

        foreach (self::BRANCH_COLUMNS as $column => $branches) {
            if (in_array($branch, $branches, true)) {
                continue;
            }

            if ($this->isTranslatableAttribute($column)) {
                if ($this->getTranslations($column) !== []) {
                    $attributes[$column] = [];
                }
            } elseif ($this->getAttribute($column) !== null) {
                $attributes[$column] = null;
            }
        }

        return $attributes;
    }

    /** Ramo del wizard di una tipologia: struttura | attivita | eventi, null se di un altro wizard. */
    private static function branchOf(string $type): ?string
    {
        return match (true) {
            in_array($type, self::STRUCTURE_TYPES, true) => 'struttura',
            in_array($type, self::ACTIVITY_TYPES, true) => $type,
            default => null,
        };
    }
}
