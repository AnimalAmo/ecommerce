<?php

namespace Tests\Feature\Catalog;

use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Il percorso completo dei campi nati dalle risposte della cliente del
 * 27/09/2026: bozza → publisher → `events` → scheda pubblica. Le prove del
 * publisher (EventPublisherTest) si fermano alla colonna; queste arrivano al
 * cliente, che è l'unico posto dove il valore conta davvero.
 *
 * La classe esiste perché nessuna di quelle in `tests/Feature/Catalog` è il
 * posto giusto: AmenityVisibilityTest copre il box "Cosa è incluso",
 * PayOnSiteNoticeTest i recapiti del partner. Qui si prova cosa la scheda dice
 * di un servizio professionale o di un evento — tipologia, zona, orari,
 * prenotazione, ricorrenza, posti — e cosa NON dice più quando una data non c'è.
 *
 * Le schede si producono pubblicando una bozza, non con `Event::factory()`:
 * senza il publisher in mezzo la prova non vedrebbe la trappola che su questo
 * repo è già costata due volte, cioè il valore che resta nella bozza perché la
 * colonna gemella non c'è.
 */
class ActivityEventSheetFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->seed(AmenitySeeder::class);
    }

    /**
     * Bozza completa e pubblicabile, nella stessa forma della fixture di
     * EventPublisherTest. Il titolare è un partner con Stripe collegato: senza
     * profilo pagabile DraftPublisher rifiuta, e senza pagamento online la
     * scheda mostrerebbe i recapiti al posto della CTA.
     */
    private function publish(array $attributes = []): Event
    {
        $draft = StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Aperitivo a 6 zampe'],
            'description' => ['it' => 'Un aperitivo con i vostri amici pelosi.'],
            'meeting_point' => ['it' => 'Piazza Duomo'],
            'address' => 'Piazza Duomo 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-12-01',
            'date_end' => '2026-12-01',
            'time_start' => '10:00',
            'time_end' => '18:00',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'cancellation_when' => '1',
            'photos' => ['structure-photos/evento.jpg'],
        ], $attributes));

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Event::class, $event, 'La bozza della prova non è arrivata a catalogo.');

        return $event;
    }

    /** Attività: nessuna data, zona al posto della data, niente ritrovo. */
    private function publishActivity(array $attributes = []): Event
    {
        return $this->publish(array_merge([
            'type' => 'attivita',
            'meeting_point' => [],
            'date_start' => null,
            'date_end' => null,
            'time_start' => null,
            'time_end' => null,
        ], $attributes));
    }

    /**
     * Orari scritti con il query builder, non col model: è l'UNICO modo di
     * provare la riga della scheda senza dipendere dal lato profilo. La colonna
     * è testo libero tradotto, serializzato in JSON — la stessa forma che
     * `PartnerProfileForm::toProfile()` manda.
     */
    private function giveOpeningHours(Event $event, array $translations): void
    {
        PartnerProfile::query()
            ->where('user_id', $event->user_id)
            ->update(['opening_hours' => json_encode($translations)]);
    }

    private function activityPage(Event $activity)
    {
        return $this->get(route('eventi.activity', ['activity' => $activity->slug]))->assertOk();
    }

    private function eventPage(Event $event)
    {
        return $this->get(route('eventi.detail', ['event' => $event->slug]))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Scheda del servizio professionale
    |--------------------------------------------------------------------------
    */

    /**
     * Il cuore della richiesta: «un servizio professionale si presenta per
     * quello che fa, non per una data». Le quattro righe nuove ci sono, la
     * coppia Check-in/Check-out no — senza data mostrava due trattini.
     */
    public function test_the_activity_sheet_shows_the_type_the_area_and_the_booking(): void
    {
        $activity = $this->publishActivity([
            'activity_categories' => ['maneggio', 'fattoria_didattica'],
            'operating_area' => ['it' => 'Milano e provincia'],
            'booking_requirement' => 'obbligatoria',
        ]);
        $this->giveOpeningHours($activity, ['it' => 'Lun-Ven 9-18']);

        $this->activityPage($activity)
            // Tipologia: le label tradotte, su una riga sola.
            ->assertSee('Maneggio / Centro equestre')
            ->assertSee('Fattoria didattica')
            ->assertSee(__('partner.activity_location.operating_area'))
            ->assertSee('Milano e provincia')
            ->assertSee(__('partner.profile.opening_hours'))
            ->assertSee('Lun-Ven 9-18')
            // Il valore si porta dietro la parola: nessuna etichetta davanti.
            ->assertSee(__('partner.booking_requirement.obbligatoria'))
            ->assertDontSee(__('events.checkin'))
            ->assertDontSee(__('events.checkout'));
    }

    /**
     * Il titolare non ha compilato gli orari: la riga non deve comparire vuota.
     * È la differenza tra «mai un'etichetta senza valore» e un «Orari di
     * apertura:» che non dice niente.
     */
    public function test_an_owner_without_opening_hours_leaves_no_empty_row(): void
    {
        $activity = $this->publishActivity(['operating_area' => ['it' => 'Milano e provincia']]);

        $this->activityPage($activity)
            ->assertSee('Milano e provincia')
            ->assertDontSee(__('partner.profile.opening_hours'));
    }

    /**
     * L'ultima gamba del percorso del testo libero di «Altro»: sotto-riga grigia
     * della tipologia, come time_note e venue_note. Senza la colonna gemella su
     * `events` resterebbe nella bozza e il cliente non leggerebbe mai la
     * tipologia che il partner ha scritto a mano.
     */
    public function test_the_activity_sheet_shows_the_free_text_of_the_other_type(): void
    {
        $activity = $this->publishActivity([
            'activity_categories' => ['altro'],
            'activity_categories_other' => ['it' => 'Pensione per conigli'],
        ]);

        $this->activityPage($activity)
            ->assertSee(__('partner.activity_category.altro'))
            ->assertSee('Pensione per conigli');
    }

    /** Nessuna zona compilata: nemmeno quella riga si disegna. */
    public function test_an_activity_without_an_operating_area_leaves_no_empty_row(): void
    {
        $activity = $this->publishActivity();

        $this->activityPage($activity)->assertDontSee(__('partner.activity_location.operating_area'));
    }

    /**
     * Il ritrovo inventato: senza punto d'incontro il Venue nasce col nome
     * vuoto, e la scheda non deve stampare «Ritrovo: <nome dell'attività>».
     */
    public function test_an_activity_without_a_meeting_point_shows_no_meeting_point_row(): void
    {
        $activity = $this->publishActivity();

        $this->activityPage($activity)->assertDontSee(__('events.meeting_point', [
            'name' => $activity->title,
            'location' => $activity->location,
        ]));
    }

    /** Il punto d'incontro vero, se un professionista lo compila, resta. */
    public function test_an_activity_with_a_real_meeting_point_keeps_the_row(): void
    {
        $activity = $this->publishActivity(['meeting_point' => ['it' => 'Ingresso del parco']]);

        $this->activityPage($activity)->assertSee(__('events.meeting_point', [
            'name' => 'Ingresso del parco',
            'location' => $activity->location,
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Scheda dell'evento
    |--------------------------------------------------------------------------
    */

    public function test_the_event_sheet_shows_the_type_the_recurrence_and_the_booking(): void
    {
        $event = $this->publish([
            'event_categories' => ['fiere_mercatini'],
            'recurrence' => 'ricorrente',
            'booking_requirement' => 'facoltativa',
        ]);

        $this->eventPage($event)
            ->assertSee('Fiere / Mercatini / Manifestazioni')
            ->assertSee(__('partner.event_recurrence.ricorrente'))
            ->assertSee(__('partner.booking_requirement.facoltativa'));
    }

    public function test_the_event_sheet_shows_the_free_text_of_the_other_type(): void
    {
        $event = $this->publish([
            'event_categories' => ['altro'],
            'event_categories_other' => ['it' => 'Sagra del cane'],
        ]);

        $this->eventPage($event)
            ->assertSee(__('partner.event_category.altro'))
            ->assertSee('Sagra del cane');
    }

    /** I posti che restano, non la capienza: la scheda dice quanti ne trova chi arriva ora. */
    public function test_the_event_sheet_shows_the_remaining_seats(): void
    {
        $event = $this->publish(['max_participants' => 30]);
        $event->update(['booked_participants' => 12]);

        $this->eventPage($event->fresh())
            ->assertSee(__('partner.activity_info.max_participants'))
            ->assertSeeInOrder([__('partner.activity_info.max_participants'), '18']);
    }

    /** Capienza illimitata: nessuna riga. È lo stato di tutte le schede pubblicate prima del 27/09/2026. */
    public function test_an_unlimited_event_shows_no_seats_row(): void
    {
        $event = $this->publish(['max_participants' => null]);

        $this->eventPage($event)->assertDontSee(__('partner.activity_info.max_participants'));
    }

    /*
    |--------------------------------------------------------------------------
    | Posti esauriti: la CTA lascia il posto alla ragione
    |--------------------------------------------------------------------------
    | Un pulsante che risponde soltanto con un errore è peggio di un pulsante
    | assente: il carrello rifiuterebbe l'aggiunta con un toast, e «Partecipa»
    | prometterebbe un posto che non c'è. La regola vera resta di
    | AvailabilityService, che conta sotto lock: qui si prova solo cosa si vede.
    */

    public function test_a_sold_out_paid_event_loses_the_cart_cta(): void
    {
        $event = $this->publish(['max_participants' => 10]);
        $event->update(['booked_participants' => 10]);

        $this->eventPage($event->fresh())
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.add_to_cart'))
            // «Posti disponibili: 0» sarebbe una riga che dice di sì e di no.
            ->assertDontSee(__('partner.activity_info.max_participants'));
    }

    public function test_a_sold_out_free_event_loses_the_join_cta(): void
    {
        $event = $this->publish([
            'price_type' => 'gratuito',
            'price_per_person' => null,
            'max_participants' => 10,
        ]);
        $event->update(['booked_participants' => 10]);

        $this->assertTrue($event->fresh()->hasJoinCta());

        $this->eventPage($event->fresh())
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.join'));
    }

    public function test_a_sold_out_paid_activity_loses_the_cart_cta(): void
    {
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 10]);

        $this->activityPage($activity->fresh())
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.add_to_cart'));
    }

    public function test_a_sold_out_free_activity_loses_the_join_cta(): void
    {
        $activity = $this->publishActivity([
            'price_type' => 'gratuito',
            'price_per_person' => null,
            'max_participants' => 10,
        ]);
        $activity->update(['booked_participants' => 10]);

        $this->assertTrue($activity->fresh()->hasJoinCta());

        $this->activityPage($activity->fresh())
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.join'));
    }

    /** Un posto libero su dieci: la CTA c'è ancora. Il negativo esatto dei quattro qui sopra. */
    public function test_an_event_with_one_seat_left_keeps_the_cart_cta(): void
    {
        $event = $this->publish(['max_participants' => 10]);
        $event->update(['booked_participants' => 9]);

        $this->eventPage($event->fresh())
            ->assertSee(__('events.add_to_cart'))
            ->assertDontSee(__('cart.sold_out'));
    }
}
