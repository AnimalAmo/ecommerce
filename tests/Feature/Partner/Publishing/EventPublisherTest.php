<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\Event\Event;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Models\Venue\Venue;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AmenitySeeder::class);
    }

    private function activityDraft(array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Aperitivo a 6 zampe', 'en' => 'Six-legged happy hour'],
            'description' => ['it' => 'Un aperitivo con i vostri amici pelosi.'],
            'meeting_point' => ['it' => 'Piazza Duomo'],
            'address' => 'Piazza Duomo 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-08-01',
            'date_end' => '2026-08-01',
            'time_start' => '10:00',
            'time_end' => '18:00',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'cancellation_when' => '1',
            'services' => ['wifi'],
            'additional_services' => ['colazione'],
            'animal_services' => ['veterinario'],
            'photos' => ['structure-photos/evento.jpg'],
        ], $attributes));
    }

    public function test_publish_carries_the_professional_categories_of_an_activity(): void
    {
        // Otto categorie a scelta multipla (cliente, 26/09/2026). Senza la
        // colonna gemella su `events` il valore resterebbe nella bozza e la
        // scheda pubblica non lo vedrebbe mai.
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'activity_categories' => ['maneggio', 'fattoria_didattica'],
        ]));

        $this->assertSame(['maneggio', 'fattoria_didattica'], $event->activity_categories);
    }

    public function test_publish_carries_the_free_text_of_the_other_category(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'activity_categories' => ['altro'],
            'activity_categories_other' => 'Pensione per conigli',
        ]));

        $this->assertSame(['altro'], $event->activity_categories);
        $this->assertSame('Pensione per conigli', $event->activity_categories_other);
    }

    public function test_an_event_does_not_carry_professional_categories(): void
    {
        // Le categorie sono del professionista, non dell'evento. Se restano
        // addosso alla bozza dopo un cambio di ramo, il publisher non deve
        // portarle a catalogo: è lo stesso difetto dei campi orfani chiuso il
        // 26/09 sul wizard.
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'activity_categories' => ['toelettatore'],
            'activity_categories_other' => 'residuo',
        ]));

        $this->assertNull($event->activity_categories);
        // Non `assertNull`: un attributo tradotto spatie legge la stringa vuota
        // quando la lingua non c'è, mai null. Le traduzioni grezze dicono la
        // cosa che conta — nessun testo, in nessuna lingua — e non dipendono
        // dal locale con cui gira la suite.
        $this->assertSame([], $event->getTranslations('activity_categories_other'));
    }

    public function test_publish_creates_a_paid_event_with_composed_datetimes(): void
    {
        $draft = $this->activityDraft();

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('event', $event->type->value);
        $this->assertSame('aperitivo-a-6-zampe-'.$draft->id, $event->slug);
        $this->assertSame('Milano (MI), Italia', $event->location);
        // Data + orario del wizard ricomposti in datetime.
        $this->assertSame('2026-08-01 10:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-08-01 18:00', $event->ends_at->format('Y-m-d H:i'));
        $this->assertSame(2500, $event->price_cents);
        $this->assertFalse($event->is_free);
        $this->assertFalse($event->hasJoinCta());
        // Capienza illimitata: nessun input wizard (v2).
        $this->assertNull($event->max_participants);
        $this->assertSame(1, $event->cancellation_policy_days);
        $this->assertSame('Aperitivo a 6 zampe', $event->getTranslation('title', 'it'));
        $this->assertSame('Six-legged happy hour', $event->getTranslation('title', 'en'));
    }

    public function test_publish_creates_the_venue_from_the_meeting_point(): void
    {
        $draft = $this->activityDraft();

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertNotNull($event->venue);
        $this->assertSame('Piazza Duomo', $event->venue->name);
        $this->assertSame('Piazza Duomo 1 20121 Milano (MI)', $event->venue->address);
        $this->assertNull($event->venue->map_img);

        // Ri-pubblicare riusa lo stesso venue e propaga le correzioni di indirizzo.
        $draft->address = 'Via Nuova 9';
        $draft->save();
        app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame(1, Venue::count());
        $this->assertSame(1, Event::count());
        $this->assertSame('Via Nuova 9 20121 Milano (MI)', $event->venue->fresh()->address);
    }

    public function test_partners_with_the_same_meeting_point_get_separate_venues(): void
    {
        // Venue scopato sul draft: niente indirizzi altrui in pagina.
        app(DraftPublisher::class)->publish($this->activityDraft());
        app(DraftPublisher::class)->publish($this->activityDraft(['address' => 'Corso Italia 5', 'city' => 'Firenze', 'province' => 'FI', 'zip' => '50121']));

        $this->assertSame(2, Venue::count());
    }

    /**
     * Era `test_publish_skips_activity_drafts_without_dates`, e il nome
     * prometteva il contrario di quel che la fixture fa: `activityDraft()` nasce
     * `type => 'eventi'`, quindi il caso provato è sempre stato quello
     * dell'EVENTO senza data. Che è il caso giusto da tenere — un evento senza
     * `starts_at` renderebbe una card cliccabile con un detail rotto — sotto il
     * nome che lo dice. Il caso dell'attività è quello qui sotto, e dal
     * 27/09/2026 va nel verso opposto.
     */
    public function test_publish_skips_event_drafts_without_dates(): void
    {
        $this->assertNull(app(DraftPublisher::class)->publish($this->activityDraft(['date_start' => null, 'date_end' => null])));
        $this->assertSame(0, Event::count());
    }

    /**
     * Il buco che non aveva nessuna prova: un servizio professionale senza data
     * DEVE arrivare a catalogo (risposta della cliente, 27/09/2026). Prima la
     * data obbligatoria per tutti teneva fuori dal catalogo, per sempre, ogni
     * dog sitter e ogni toelettatore.
     */
    public function test_publish_carries_an_activity_without_dates_to_the_catalog(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'date_start' => null,
            'date_end' => null,
            'time_start' => null,
            'time_end' => null,
        ]));

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('activity', $event->type->value);
        // Nessuna data inventata a valle: `composeDateTime` torna null, e senza
        // le due date `durationDays` non può calcolare niente.
        $this->assertNull($event->starts_at);
        $this->assertNull($event->ends_at);
        $this->assertNull($event->duration_days);
    }

    public function test_publish_creates_a_free_activity_with_duration(): void
    {
        $draft = $this->activityDraft([
            'type' => 'attivita',
            'date_start' => '2026-09-04',
            'date_end' => '2026-09-06',
            'time_start' => null,
            'time_end' => null,
            'price_type' => 'gratuito',
            'price_per_person' => null,
        ]);

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertSame('activity', $event->type->value);
        // Senza durata il detail attività farebbe fallback sul mock '3 giorni'.
        $this->assertSame(3, $event->duration_days);
        $this->assertSame('2026-09-04 00:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-06 23:59', $event->ends_at->format('Y-m-d H:i'));
        $this->assertNull($event->price_cents);
        $this->assertTrue($event->is_free);
        $this->assertTrue($event->hasJoinCta());
    }

    /*
    |--------------------------------------------------------------------------
    | Colonne nate dalle risposte della cliente del 27/09/2026
    |--------------------------------------------------------------------------
    | Ognuna ha la sua gemella su `events`: è la trappola che su questo repo è
    | già costata due volte, perché senza la colonna a valle il valore resta
    | nella bozza e la scheda pubblica non lo vede mai. Le guardie sul tipo si
    | provano nei due versi: quel che il ramo non pubblica NON deve arrivare,
    | perché un cambio di ramo può lasciare il valore addosso alla bozza.
    */

    public function test_publish_carries_the_event_types(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'event_categories' => ['fiere_mercatini', 'altro'],
            'event_categories_other' => 'Sagra del cane',
        ]));

        $this->assertSame(['fiere_mercatini', 'altro'], $event->event_categories);
        $this->assertSame('Sagra del cane', $event->event_categories_other);
    }

    public function test_an_activity_does_not_carry_event_types(): void
    {
        // «Fiere / Mercatini» non descrive un servizio professionale: se lo slug
        // resta sulla bozza dopo un cambio di ramo, non deve arrivare a catalogo.
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'event_categories' => ['fiere_mercatini'],
            'event_categories_other' => 'residuo',
        ]));

        $this->assertNull($event->event_categories);
        // Non `assertNull`: un attributo tradotto spatie legge la stringa vuota
        // quando la lingua non c'è, mai null.
        $this->assertSame([], $event->getTranslations('event_categories_other'));
    }

    public function test_publish_carries_the_operating_area_of_an_activity(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'meeting_point' => [],
            'operating_area' => ['it' => 'Milano e provincia', 'en' => 'Milan and province'],
        ]));

        $this->assertSame('Milano e provincia', $event->getTranslation('operating_area', 'it'));
        $this->assertSame('Milan and province', $event->getTranslation('operating_area', 'en'));
    }

    public function test_an_event_does_not_carry_the_operating_area(): void
    {
        // La zona sta AL POSTO del punto d'incontro: dove un ritrovo c'è, non
        // vale.
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'operating_area' => ['it' => 'Lombardia'],
        ]));

        $this->assertSame([], $event->getTranslations('operating_area'));
    }

    /**
     * Senza punto d'incontro il Venue prendeva il NOME DEL SERVIZIO, e la scheda
     * stampava «Ritrovo: <nome dell'attività>» — un ritrovo che non esiste. Il
     * Venue continua a nascere (regge indirizzo e mappa), col nome vuoto, che è
     * il segnale su cui la scheda decide se stampare quella riga.
     */
    public function test_an_activity_without_a_meeting_point_gets_a_venue_without_a_name(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'meeting_point' => [],
        ]));

        $this->assertNotNull($event->venue);
        $this->assertSame('', $event->venue->name);
        $this->assertSame('Piazza Duomo 1 20121 Milano (MI)', $event->venue->address);
        $this->assertNotSame($event->title, $event->venue->name);
    }

    public function test_publish_carries_the_recurrence_of_an_event(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft(['recurrence' => 'ricorrente']));

        $this->assertSame('ricorrente', $event->recurrence);
    }

    public function test_an_activity_does_not_carry_the_recurrence(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'recurrence' => 'ricorrente',
        ]));

        $this->assertNull($event->recurrence);
    }

    /**
     * La prenotazione NON ha guardie sul tipo, ed è voluto: la cliente la chiede
     * ai professionisti come «possibilità di prenotazione» e agli eventi come
     * «obbligatoria o facoltativa», cioè è la stessa informazione.
     */
    public function test_publish_carries_the_booking_requirement_on_both_branches(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft(['booking_requirement' => 'obbligatoria']));
        $activity = app(DraftPublisher::class)->publish($this->activityDraft([
            'type' => 'attivita',
            'booking_requirement' => 'facoltativa',
        ]));

        $this->assertSame('obbligatoria', $event->booking_requirement);
        $this->assertSame('facoltativa', $activity->booking_requirement);
    }

    /**
     * Capienza: dal 27/09/2026 il wizard ha il campo, quindi un evento può
     * finalmente esaurirsi. Nessuna guardia sul tipo — un workshop ha dei posti
     * anche se è un'attività — e NULL resta "illimitata", che è quello che hanno
     * tutte le schede pubblicate prima della modifica.
     */
    public function test_publish_carries_the_seats(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft(['max_participants' => 30]));

        $this->assertSame(30, $event->max_participants);
    }

    public function test_seats_left_empty_stay_unlimited(): void
    {
        $event = app(DraftPublisher::class)->publish($this->activityDraft(['max_participants' => null]));

        $this->assertNull($event->max_participants);
    }
}
