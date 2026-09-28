<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\EventDetail;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    /*
    |--------------------------------------------------------------------------
    | Difetto C2 — l'attività conta i posti sulla persona sbagliata
    |--------------------------------------------------------------------------
    | `ActivityDetail::isSoldOut()` è `booked >= max`, cioè la soglia della
    | persona minima; il widget nasce con 2 adulti e
    | `AvailabilityService::ensureEventAvailable()` rifiuta con
    | `booked + persons > max`. Fra le due soglie la CTA resta accesa e risponde
    | soltanto con un toast — il pulsante che un pulsante assente batte.
    */

    public function test_unattivita_con_meno_posti_degli_ospiti_di_default_non_offre_il_carrello(): void
    {
        // Un posto libero su dieci, e il widget chiede per due.
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 9]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            // I default del widget non sono clampati sulla capienza residua.
            ->assertSet('editGuests', ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0])
            ->call('addToCart')
            // Oggi: toast «Non ci sono abbastanza posti disponibili» e nient'altro.
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->activityPage($activity->fresh())->assertDontSee(__('events.add_to_cart'));
    }

    /**
     * L'altra metà di C2: senza sapere quanti posti restano, il cliente non ha
     * modo di capire che scendendo a un ospite l'acquisto passerebbe. Prima
     * della correzione `remainingSeats` esisteva solo su EventDetail; ora
     * ActivityDetail::render() lo passa anche alla scheda attività.
     *
     * Riscritta il 28/09/2026: la versione precedente cercava l'etichetta e poi
     * un '3' QUALSIASI dopo di lei (`assertSeeInOrder`), e un '3' dopo quel
     * punto della pagina c'è sempre — `mt-3`, prezzi, date. Passava con
     * qualunque numero di posti. Ora si guarda la riga intera, col valore.
     */
    public function test_la_scheda_attivita_dice_quanti_posti_restano(): void
    {
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 7]);

        $label = __('partner.activity_info.max_participants');

        $this->activityPage($activity->fresh())
            ->assertSeeText($label.': 3')
            // Né la capienza totale né i venduti al posto dei residui.
            ->assertDontSeeText($label.': 10')
            ->assertDontSeeText($label.': 7')
            // Tre posti per due ospiti di default: la CTA c'è.
            ->assertSee(__('events.add_to_cart'));
    }

    /** Capienza illimitata (`max_participants` nullo, tutte le schede pre-27/09): nessuna riga, non «illimitati». */
    public function test_un_attivita_senza_capienza_non_mostra_la_riga_dei_posti(): void
    {
        $activity = $this->publishActivity(['max_participants' => null]);

        $this->activityPage($activity)
            ->assertDontSee(__('partner.activity_info.max_participants'))
            ->assertSee(__('events.add_to_cart'));
    }

    /**
     * Posti che non bastano per gli ospiti scelti: al posto della CTA la
     * ragione e il numero di posti che restano, così il cliente sa di quanto
     * scendere.
     */
    public function test_con_posti_insufficienti_la_scheda_dice_quanti_ne_restano_al_posto_della_cta(): void
    {
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 9]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            ->assertSet('editGuests', ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0])
            ->assertSee(__('cart.sold_out'))
            ->assertSeeText(__('partner.activity_info.max_participants').': 1')
            ->assertDontSeeHtml('wire:click="addToCart"');
    }

    /**
     * Un posto libero e un adulto: la richiesta sta nella capienza, quindi la
     * CTA torna e il carrello la accetta davvero. È il «−» che la dicitura
     * suggerisce al cliente.
     */
    public function test_con_un_posto_libero_e_un_adulto_la_cta_ce_e_aggiunge(): void
    {
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 9]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            ->call('decrementGuest', 'adulti')
            ->assertSet('editGuests', ['adulti' => 1, 'ragazzi' => 0, 'bambini' => 0])
            ->assertSeeHtml('wire:click="addToCart"')
            ->assertSee(__('events.add_to_cart'))
            ->assertDontSee(__('cart.sold_out'))
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $this->assertCount(1, app(CartManager::class)->items());
    }

    /**
     * Lo stepper non porta il cliente in uno stato che il carrello rifiuta:
     * con tre posti liberi si sale fino a tre ospiti, in qualunque fascia, e lì
     * ci si ferma. `incrementGuest()` si chiama direttamente, come farebbe un
     * payload wire, perché il clamp deve valere lato server e non solo sul
     * pulsante disabilitato.
     */
    public function test_lo_stepper_ospiti_non_supera_i_posti_residui(): void
    {
        $activity = $this->publishActivity(['max_participants' => 10]);
        $activity->update(['booked_participants' => 7]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            ->call('incrementGuest', 'bambini')
            ->assertSet('editGuests', ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 1])
            ->call('incrementGuest', 'adulti')
            ->call('incrementGuest', 'ragazzi')
            ->call('incrementGuest', 'bambini')
            ->assertSet('editGuests', ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 1])
            // Al limite la CTA resta: tre ospiti su tre posti è una richiesta valida.
            ->assertSee(__('events.add_to_cart'))
            ->call('addToCart')
            ->assertSet('cartPopupOpen', true);

        $this->assertCount(1, app(CartManager::class)->items());
    }

    /**
     * Il clamp sui posti è un override di ActivityDetail: il trait
     * HasBookingCalendar lo condivide con strutture e servizi, e senza capienza
     * deve restare il tetto fisso di sempre (MAX_GUESTS = 10), non sparire.
     */
    public function test_senza_capienza_lo_stepper_resta_al_tetto_fisso(): void
    {
        $activity = $this->publishActivity(['max_participants' => null]);

        $component = Livewire::test(ActivityDetail::class, ['activity' => $activity->slug]);

        foreach (range(1, 12) as $click) {
            $component->call('incrementGuest', 'adulti');
        }

        $component->assertSet('editGuests.adulti', 10);
    }

    /*
    |--------------------------------------------------------------------------
    | Difetto C4 — «Partecipa» non registra niente
    |--------------------------------------------------------------------------
    */

    /** Evento gratuito pubblicato, con capienza dichiarata. */
    private function publishFreeEvent(int $seats = 20): Event
    {
        return $this->publish([
            'price_type' => 'gratuito',
            'price_per_person' => null,
            'max_participants' => $seats,
        ]);
    }

    /**
     * `joinEvent()` apre solo il pop-up (il TODO è dichiarato nel metodo), e
     * `ProfileEvents::bookedEvents()` legge `order_items`, che per un evento
     * gratuito non esistono: «Eventi a cui partecipo» resta vuoto per sempre.
     */
    public function test_partecipare_a_un_evento_gratuito_lo_fa_comparire_fra_i_miei_eventi(): void
    {
        $event = $this->publishFreeEvent();
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('joinEvent')
            ->assertSet('joinPopupOpen', true);

        $this->get(route('profilo.eventi'))
            ->assertOk()
            ->assertSee($event->title);
    }

    /**
     * L'unico punto che incrementa `booked_participants` è
     * ReserveAvailabilityPipe, raggiungibile solo da una riga di carrello, e
     * `hasJoinCta()` fa uscire subito `addToCart()`. Per un evento gratuito il
     * contatore resta quindi a zero qualunque cosa faccia il pubblico: il
     * blocco a posti esauriti non può scattare, e la scheda continua a
     * promettere la capienza piena.
     */
    public function test_le_partecipazioni_a_un_evento_gratuito_consumano_i_posti(): void
    {
        $event = $this->publishFreeEvent(2);

        foreach ([User::factory()->create(), User::factory()->create()] as $participant) {
            $this->actingAs($participant);

            Livewire::test(EventDetail::class, ['event' => $event->slug])
                ->call('joinEvent')
                ->assertSet('joinPopupOpen', true);
        }

        $this->assertSame(
            2,
            (int) $event->fresh()->booked_participants,
            'Due partecipazioni confermate devono consumare i due posti dichiarati: '
            .'finché il contatore non si muove, isSoldOut() non diventa mai vero.',
        );

        // Terzo visitatore: i posti sono finiti e la CTA deve lasciare il posto alla ragione.
        $this->eventPage($event->fresh())
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.join'));
    }

    /**
     * Finché la partecipazione reale non c'è, la copy non deve affermare un
     * fatto compiuto: il pop-up titola «Aggiunto agli eventi» e l'evento non è
     * stato aggiunto da nessuna parte. Identico su ActivityDetail.
     */
    public function test_il_popup_della_partecipazione_non_dichiara_un_fatto_che_non_e_avvenuto(): void
    {
        $event = $this->publishFreeEvent();
        $this->actingAs(User::factory()->create());

        $html = Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('joinEvent')
            ->html();

        $this->assertStringNotContainsString(
            __('events.added_to_events'),
            $html,
            'Nessun ordine, nessuna riga di partecipazione e nessun posto consumato: '
            .'il pop-up non può dire «Aggiunto agli eventi».',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Difetto W1 — detailed_description: obbligatoria e illeggibile
    |--------------------------------------------------------------------------
    */

    /**
     * `ActivityDescription::next()` rende `detailedDescription.it` `required`
     * per il ramo Attività/Servizio professionale e la salva su
     * `structure_drafts.detailed_description`. EventPublisher non nomina mai
     * quella colonna e su `events` non esiste la gemella (la smartbox ce l'ha:
     * `extended_description`), quindi il partner compila un campo senza il
     * quale non avanza e che nessuno leggerà mai.
     */
    public function test_la_descrizione_dettagliata_di_un_servizio_professionale_arriva_alla_scheda(): void
    {
        $activity = $this->publishActivity([
            'detailed_description' => ['it' => 'Lavoro su appuntamento con un educatore certificato ENCI.'],
        ]);

        $this->activityPage($activity)->assertSee('Lavoro su appuntamento con un educatore certificato ENCI.');
    }

    /**
     * L'altra faccia dello stesso buco: al posto della dettagliata, la seconda
     * sezione «Attività» ristampa la descrizione breve — lo stesso testo due
     * volte nella stessa pagina.
     */
    public function test_la_scheda_attivita_non_ripete_due_volte_la_descrizione_breve(): void
    {
        $activity = $this->publishActivity([
            'description' => ['it' => 'Educazione di base con rinforzo positivo.'],
        ]);

        $html = $this->activityPage($activity)->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Educazione di base con rinforzo positivo.'),
            'La descrizione breve compare una volta sola: la sezione «Attività» deve portare '
            .'la descrizione dettagliata, non ripetere quella di sopra.',
        );
    }

    /**
     * Con la dettagliata, la sezione «Attività» c'è e porta QUEL testo, una
     * volta; la breve resta nella sua sezione e non si ripete.
     */
    public function test_la_sezione_attivita_porta_la_dettagliata_e_la_breve_resta_una(): void
    {
        $activity = $this->publishActivity([
            'description' => ['it' => 'Breve: educazione di base.'],
            'detailed_description' => ['it' => 'Lunga: percorso in sei incontri con verifica finale.'],
        ]);

        $html = $this->activityPage($activity)
            ->assertSeeHtml('>'.__('events.activity').'</h2>')
            ->assertSeeInOrder([__('events.activity'), 'Lunga: percorso in sei incontri con verifica finale.'])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Breve: educazione di base.'));
        $this->assertSame(1, substr_count($html, 'Lunga: percorso in sei incontri con verifica finale.'));
    }

    /**
     * Senza dettagliata (catalogo demo, attività non raggiunte dal travaso) la
     * sezione sparisce: titolo compreso, non un «Attività» con niente sotto.
     * La colonna tradotta legge '' e non null (spatie), quindi la guardia del
     * blade deve essere filled(), e questa prova lo tiene fermo.
     */
    public function test_la_sezione_attivita_sparisce_senza_dettagliata(): void
    {
        $activity = $this->publishActivity([
            'description' => ['it' => 'Solo la breve.'],
            'detailed_description' => null,
        ]);

        $this->assertSame([], $activity->getTranslations('detailed_description'));

        $this->activityPage($activity)
            ->assertSee('Solo la breve.')
            ->assertDontSeeHtml('>'.__('events.activity').'</h2>');
    }

    /*
    |--------------------------------------------------------------------------
    | Buco di copertura 3 — la zona operativa non finisce su un evento
    |--------------------------------------------------------------------------
    | La guardia nel publisher era provata; che la riga non compaia sulla scheda
    | di un evento no. La zona sta AL POSTO del punto d'incontro: un evento ha
    | un ritrovo, non un territorio.
    */

    public function test_la_scheda_di_un_evento_non_mostra_la_zona_in_cui_opera(): void
    {
        // La bozza porta addosso la zona (cambio di ramo da Attività a Evento).
        $event = $this->publish(['operating_area' => ['it' => 'Milano e provincia']]);

        $this->assertNull($event->operating_area, 'Il publisher non deve copiare la zona su un evento.');

        $this->eventPage($event)
            ->assertDontSee(__('partner.activity_location.operating_area'))
            ->assertDontSee('Milano e provincia');
    }
}
