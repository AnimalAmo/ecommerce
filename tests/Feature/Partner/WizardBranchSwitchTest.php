<?php

namespace Tests\Feature\Partner;

use App\Enums\OrderStatus;
use App\Livewire\Partner\Activity\ActivityType;
use App\Livewire\Partner\MyServices\DeleteServiceModal;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * «Il percorso di registrazione dovrebbe cambiare automaticamente in base alla
 * scelta effettuata» (richiesta della cliente, 29/09/2026).
 *
 * I due wizard scrivono la STESSA colonna `structure_drafts.type` — uno con
 * hotel|bb|agriturismo|casa_vacanza, l'altro con attivita|eventi — e finora
 * nessuno dei due la ripuliva passando dall'uno all'altro. Restavano addosso
 * alla bozza i campi del ramo abbandonato, che finivano in pubblicazione.
 */
class WizardBranchSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function openDraft(array $attributes): StructureDraft
    {
        $partner = $this->actingAsActivePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 1,
            ...$attributes,
        ]);

        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    public function test_the_structure_step_does_not_preload_an_activity_type(): void
    {
        // Senza la guardia il radio group precaricava 'eventi', che nessuna
        // card mostra e che il validatore rifiuta: il partner vedeva un errore
        // su una scelta che non aveva fatto.
        $this->openDraft(['service_category' => 'attivita', 'type' => 'eventi']);

        Livewire::test(StructureType::class)->assertSet('type', '');
    }

    public function test_the_activity_step_does_not_preload_a_structure_type(): void
    {
        $this->openDraft(['service_category' => 'struttura', 'type' => 'agriturismo']);

        Livewire::test(ActivityType::class)->assertSet('type', '');
    }

    public function test_switching_to_a_structure_clears_the_activity_fields(): void
    {
        $draft = $this->openDraft([
            'service_category' => 'attivita',
            'type' => 'eventi',
            'meeting_point' => ['it' => 'Parco Sempione'],
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'time_start' => '09:00',
            'time_end' => '18:00',
        ]);

        Livewire::test(StructureType::class)
            ->set('type', 'hotel')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('hotel', $draft->type);
        $this->assertTrue(blank($draft->getTranslation('meeting_point', 'it')));
        $this->assertNull($draft->date_start);
        $this->assertNull($draft->date_end);
        $this->assertNull($draft->time_start);
        $this->assertNull($draft->time_end);
    }

    public function test_switching_to_an_activity_clears_the_structure_fields(): void
    {
        $draft = $this->openDraft([
            'service_category' => 'struttura',
            'type' => 'hotel',
            'license' => 'CIR-123',
            'rooms' => [['name' => 'Camera doppia', 'guests' => 2]],
            'checkin_from' => '14:00',
            'checkout_to' => '10:00',
        ]);

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('attivita', $draft->type);
        $this->assertNull($draft->license);
        $this->assertNull($draft->rooms);
        $this->assertNull($draft->checkin_from);
        $this->assertNull($draft->checkout_to);
    }

    public function test_switching_from_an_event_to_an_activity_drops_the_times(): void
    {
        // Un'attività non ha orario di inizio e fine (29/09/2026): restando
        // scritti, EventPublisher li comporrebbe comunque nei datetime.
        $draft = $this->openDraft([
            'service_category' => 'attivita',
            'type' => 'eventi',
            'date_start' => '2026-10-01',
            'time_start' => '09:00',
            'time_end' => '18:00',
        ]);

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertNull($draft->time_start);
        $this->assertNull($draft->time_end);
        // La data invece resta: le attività ce l'hanno.
        $this->assertNotNull($draft->date_start);
    }

    public function test_confirming_the_same_branch_keeps_the_work_done(): void
    {
        // Ripassare dallo step senza cambiare scelta non deve cancellare nulla.
        $draft = $this->openDraft([
            'service_category' => 'attivita',
            'type' => 'eventi',
            'date_start' => '2026-10-01',
            'time_start' => '09:00',
            'time_end' => '18:00',
        ]);

        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('09:00', $draft->time_start);
        $this->assertSame('18:00', $draft->time_end);
    }

    // ── Buco di copertura 1: le colonne del 26-27/09 nelle due liste ─────────
    //
    // Nessun test toccava le colonne nuove. È il buco più grosso perché
    // EventPublisher copia `max_participants` SENZA guardare il tipo: un residuo
    // dell'evento abbandonato farebbe esaurire un servizio professionale.

    /** Bozza evento con tutte le colonne nate dalle risposte del 26-27/09/2026. */
    private function fullEventDraft(array $attributes = []): StructureDraft
    {
        return $this->openDraft(array_merge([
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sagra del cane'],
            'meeting_point' => ['it' => 'Parco Sempione'],
            'time_start' => '09:00',
            'time_end' => '18:00',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'recurrence' => 'ricorrente',
            'max_participants' => 30,
            'event_categories' => ['fiere_mercatini'],
            'event_categories_other' => ['it' => 'Sagra paesana'],
            'operating_area' => ['it' => 'Milano e provincia'],
            // Le due che la cliente chiede a entrambi i rami, o che sono
            // l'identità del professionista: devono SOPRAVVIVERE al cambio.
            'booking_requirement' => 'obbligatoria',
            'activity_categories' => ['maneggio'],
            'activity_categories_other' => ['it' => 'Pensione per conigli'],
        ], $attributes));
    }

    public function test_switching_to_an_activity_clears_the_event_only_columns(): void
    {
        $draft = $this->fullEventDraft();

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        // Azzerate: descrivono un evento, non un servizio professionale.
        $this->assertNull($draft->recurrence);
        $this->assertNull(
            $draft->max_participants,
            'max_participants è quello che pesa: il publisher lo copia senza guardare il tipo, '
            .'quindi un residuo farebbe esaurire un servizio professionale.',
        );
        $this->assertNull($draft->event_categories);
        $this->assertTrue(blank($draft->getTranslation('event_categories_other', 'it')));
        $this->assertTrue(blank($draft->getTranslation('meeting_point', 'it')));
        $this->assertNull($draft->time_start);
        $this->assertNull($draft->time_end);
    }

    public function test_switching_to_an_activity_keeps_what_belongs_to_the_professional(): void
    {
        $draft = $this->fullEventDraft();

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        // Le categorie professionali sono l'identità di chi lavora, non
        // dell'attività: azzerarle farebbe ricompilare a un maneggio che apre un
        // evento la risposta che aveva già dato.
        $this->assertSame(['maneggio'], $draft->activity_categories);
        $this->assertSame('Pensione per conigli', $draft->getTranslation('activity_categories_other', 'it'));
        // La cliente la chiede a entrambi i rami: la risposta resta valida.
        $this->assertSame('obbligatoria', $draft->booking_requirement);
        // Anche le attività possono avere delle date.
        $this->assertNotNull($draft->date_start);
        $this->assertNotNull($draft->date_end);
        // La zona operativa è di chi lavora su un territorio: arrivando qui serve.
        $this->assertSame('Milano e provincia', $draft->getTranslation('operating_area', 'it'));
    }

    public function test_switching_to_an_event_drops_the_operating_area(): void
    {
        // Un evento ha un punto d'incontro, non un territorio.
        $draft = $this->openDraft([
            'service_category' => 'attivita',
            'type' => 'attivita',
            'operating_area' => ['it' => 'Milano e provincia'],
            'activity_categories' => ['maneggio'],
        ]);

        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertTrue(blank($draft->getTranslation('operating_area', 'it')));
    }

    // ── Difetto F5 (= W10): la lista gemella di StructureType è rimasta ferma ─

    /**
     * (Com'era prima del 28/09/2026, quando le due liste sono diventate una,
     * StructureDraft::BRANCH_COLUMNS.) `ActivityType::clearedFields()` era stata
     * estesa alle colonne del 26-27/09/2026, `StructureType::clearedFields()` no: azzerava le sei vecchie
     * e lascia posti, ricorrenza, tipologie di evento e zona operativa.
     */
    public function test_switching_to_a_structure_clears_the_event_columns_too(): void
    {
        $draft = $this->fullEventDraft();

        Livewire::test(StructureType::class)
            ->set('type', 'hotel')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('hotel', $draft->type);
        $this->assertNull(
            $draft->max_participants,
            'Passando a una struttura ricettiva i posti dell\'evento abbandonato non devono restare: '
            .'`next()` non riscrive `service_category`, quindi la bozza pubblica ancora da EventPublisher.',
        );
        $this->assertNull($draft->recurrence);
        $this->assertNull($draft->event_categories);
        $this->assertTrue(blank($draft->getTranslation('event_categories_other', 'it')));
        $this->assertTrue(blank($draft->getTranslation('operating_area', 'it')));
    }

    /**
     * Il danno a valle, che è la ragione per cui F5 è attiva e non latente:
     * `family()` resta 'attivita' perché `next()` riscrive solo `type`, quindi
     * la pubblicazione passa ancora da EventPublisher e a catalogo esce
     * un'Attività con il limite di posti dell'evento abbandonato — esauribile.
     */
    public function test_after_switching_to_a_structure_nothing_is_published_with_the_event_seats(): void
    {
        $partner = $this->actingAsPayablePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 1,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sagra del cane'],
            'description' => ['it' => 'Una sagra a sei zampe.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'max_participants' => 30,
            'price_type' => 'pagamento',
            'price_per_person' => '20',
            'cancellation_when' => '1',
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(StructureType::class)
            ->set('type', 'hotel')
            ->call('next')
            ->assertHasNoErrors();

        // Riscritto dal tester il 28/09/2026: la versione di prima era verde a
        // vuoto. Senza stanze una struttura non è pubblicabile, publish()
        // tornava null e `$published?->max_participants` era null qualunque
        // cosa facesse la correzione. Qui il partner compila le stanze come
        // farebbe allo step 5, così la bozza va DAVVERO a catalogo, e la prova
        // guarda da quale publisher esce.
        $draft->refresh()->update([
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
        ]);

        $published = app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertInstanceOf(
            Structure::class,
            $published,
            'Una bozza diventata hotel deve uscire da StructurePublisher: col solo `type` riscritto '
            .'usciva da EventPublisher come Attività, esauribile coi posti dell\'evento abbandonato.',
        );
        $this->assertFalse(
            Event::withHidden()->where('structure_draft_id', $draft->id)->exists(),
            'Nessuna riga `events` deve nascere da una bozza che ora è una struttura.',
        );
    }

    public function test_confirming_the_same_structure_type_keeps_the_work_done(): void
    {
        $draft = $this->openDraft([
            'service_category' => 'struttura',
            'type' => 'hotel',
            'rooms' => [['name' => 'Camera doppia', 'guests' => 2]],
        ]);

        Livewire::test(StructureType::class)
            ->set('type', 'bb')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('bb', $draft->type);
        $this->assertNotNull($draft->rooms);
    }

    // ── Giro del tester, 28/09/2026: F5 in tutte le direzioni ────────────────
    //
    // La correzione ha una regola sola, StructureDraft::attributesForType(), per
    // i due step del tipo. Le prove della sezione F5 guardavano un verso solo
    // (evento → hotel) e solo la traduzione italiana: qui gli altri versi, le
    // traduzioni inglesi, la famiglia riscritta e le bozze storiche `servizi`.

    /** Bozza di servizio professionale con tutte le colonne del suo ramo, in due lingue dove sono tradotte. */
    private function fullActivityDraft(array $attributes = []): StructureDraft
    {
        return $this->openDraft(array_merge([
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Toelettatura Bau', 'en' => 'Bau grooming'],
            'description' => ['it' => 'Toelettatura a domicilio.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'activity_categories' => ['toelettatore', 'altro'],
            'activity_categories_other' => ['it' => 'Pensione per conigli', 'en' => 'Rabbit boarding'],
            'operating_area' => ['it' => 'Milano e provincia', 'en' => 'Milan and surroundings'],
            'detailed_description' => ['it' => 'Su appuntamento.', 'en' => 'By appointment.'],
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'booking_requirement' => 'obbligatoria',
            'photos' => ['structure-photos/uno.jpg'],
            'cancellation_when' => '7',
        ], $attributes));
    }

    /** Bozza hotel con tutte le colonne del ramo struttura. */
    private function fullHotelDraft(array $attributes = []): StructureDraft
    {
        return $this->openDraft(array_merge([
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice'],
            'license' => 'CIR-123',
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
            'checkin_from' => '14:00',
            'checkin_to' => '20:00',
            'checkout_from' => '08:00',
            'checkout_to' => '10:00',
            'smartbox_consent' => 'si',
            'smartbox_types' => ['benessere'],
        ], $attributes));
    }

    public function test_da_servizio_professionale_a_struttura_si_azzerano_tutte_le_colonne_della_famiglia_attivita(): void
    {
        $draft = $this->fullActivityDraft();

        Livewire::test(StructureType::class)
            ->set('type', 'hotel')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('hotel', $draft->type);
        $this->assertSame('struttura', $draft->service_category, 'La famiglia decide il publisher: deve seguire il tipo.');
        $this->assertNull($draft->activity_categories);
        $this->assertNull($draft->date_start);
        $this->assertNull($draft->date_end);
        $this->assertNull($draft->booking_requirement);
        // Tradotte: svuotate in TUTTE le lingue, non solo nella corrente. Con
        // un NULL spatie avrebbe tolto solo l'italiano.
        $this->assertSame([], $draft->getTranslations('activity_categories_other'));
        $this->assertSame([], $draft->getTranslations('operating_area'));
        $this->assertSame([], $draft->getTranslations('detailed_description'));
    }

    public function test_da_struttura_a_evento_si_azzerano_le_colonne_della_struttura_e_la_famiglia_diventa_attivita(): void
    {
        $draft = $this->fullHotelDraft();

        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('eventi', $draft->type);
        $this->assertSame('attivita', $draft->service_category);
        $this->assertNull($draft->license);
        $this->assertNull($draft->rooms);
        $this->assertNull($draft->checkin_from);
        $this->assertNull($draft->checkin_to);
        $this->assertNull($draft->checkout_from);
        $this->assertNull($draft->checkout_to);
        // L'adesione alle smartbox è una domanda del solo percorso struttura.
        $this->assertNull($draft->smartbox_consent);
        $this->assertNull($draft->smartbox_types);
    }

    /** Nel verso struttura → attività la bozza deve pubblicare da EventPublisher, non più da StructurePublisher. */
    public function test_una_struttura_diventata_attivita_esce_a_catalogo_come_attivita(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 1,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Maneggio del lago'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh()->update(['status' => StructureDraft::STATUS_COMPLETED, 'current_step' => 11]);

        $published = app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertInstanceOf(Event::class, $published);
        $this->assertSame('activity', $published->type->value);
        $this->assertFalse(Structure::withHidden()->where('structure_draft_id', $draft->id)->exists());
    }

    /** I due versi interni alla famiglia attività, con la traduzione inglese: sparisce con l'italiano. */
    public function test_cambiando_ramo_le_traduzioni_inglesi_se_ne_vanno_con_litaliano(): void
    {
        $draft = $this->fullEventDraft([
            'meeting_point' => ['it' => 'Parco Sempione', 'en' => 'Sempione Park'],
            'event_categories_other' => ['it' => 'Sagra paesana', 'en' => 'Village fair'],
        ]);

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame([], $draft->getTranslations('meeting_point'));
        $this->assertSame([], $draft->getTranslations('event_categories_other'));

        // E nel verso opposto la zona, anche in inglese.
        $draft->update(['operating_area' => ['it' => 'Milano e provincia', 'en' => 'Milan and surroundings']]);

        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame([], $draft->refresh()->getTranslations('operating_area'));
    }

    /** Il cambio di ramo tocca solo le colonne di ramo: nome, luogo, descrizione, foto e cancellazione restano. */
    public function test_cambiando_ramo_le_colonne_comuni_restano(): void
    {
        $draft = $this->fullActivityDraft();

        Livewire::test(StructureType::class)
            ->set('type', 'agriturismo')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame(['it' => 'Toelettatura Bau', 'en' => 'Bau grooming'], $draft->getTranslations('name'));
        $this->assertSame('Toelettatura a domicilio.', $draft->getTranslation('description', 'it'));
        $this->assertSame('Via Roma 1', $draft->address);
        $this->assertSame('Milano', $draft->city);
        $this->assertSame(['structure-photos/uno.jpg'], $draft->photos);
        $this->assertSame('7', $draft->cancellation_when);
    }

    /**
     * «Si azzera solo ciò che c'è»: una bozza appena nata che sceglie il tipo
     * non si ritrova `[]` scritto sulle colonne tradotte, restano NULL come la
     * migrazione le ha create.
     */
    public function test_una_bozza_appena_nata_resta_a_null_dopo_la_scelta_del_tipo(): void
    {
        $draft = $this->openDraft(['service_category' => 'attivita']);

        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $raw = DB::table('structure_drafts')->where('id', $draft->id)->first();

        $this->assertSame('eventi', $raw->type);
        $this->assertNull($raw->operating_area);
        $this->assertNull($raw->detailed_description);
        $this->assertNull($raw->rooms);
    }

    /**
     * Bozze storiche `servizi`: compilate col percorso hotel, sono strutture
     * (familyOf). Passando a un servizio professionale la famiglia diventa
     * `attivita` e le colonne della struttura si azzerano, come per una
     * `struttura`.
     */
    public function test_una_bozza_storica_servizi_che_diventa_attivita_cambia_famiglia(): void
    {
        $draft = $this->fullHotelDraft(['service_category' => 'servizi']);

        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('attivita', $draft->service_category);
        $this->assertSame('attivita', $draft->family());
        $this->assertNull($draft->rooms);
        $this->assertNull($draft->license);
    }

    /** …e restando struttura non si riscrive niente: né la famiglia né il lavoro fatto. */
    public function test_una_bozza_storica_servizi_che_resta_struttura_non_cambia(): void
    {
        $draft = $this->fullHotelDraft(['service_category' => 'servizi']);

        Livewire::test(StructureType::class)
            ->set('type', 'bb')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('bb', $draft->type);
        $this->assertSame('servizi', $draft->service_category, 'Le bozze storiche `servizi` restano come sono (familyOf le manda già a StructurePublisher).');
        $this->assertNotNull($draft->rooms);
        $this->assertSame('CIR-123', $draft->license);
        $this->assertSame('si', $draft->smartbox_consent);
    }

    /**
     * Trovato dal tester il 28/09/2026 dentro la correzione F5, chiuso lo
     * stesso giorno. Lo step del tipo riscrive la famiglia, quindi un servizio
     * pubblicato come evento e riaperto (per URL) come hotel pubblica una
     * struttura nuova: publish() toglie la riga dell'altra famiglia, e
     * unpublish() («Elimina») quelle di tutte le famiglie.
     */
    public function test_un_servizio_pubblicato_che_cambia_famiglia_non_lascia_online_la_riga_vecchia(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sagra del cane'],
            'description' => ['it' => 'Una sagra a sei zampe.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-12-01',
            'cancellation_when' => '1',
        ]);
        $this->assertInstanceOf(Event::class, app(DraftPublisher::class)->publish($draft));

        // «Modifica» mette la bozza in sessione; il partner apre lo step del
        // tipo della struttura e sceglie hotel, poi compila le stanze.
        session(['structure_draft_id' => $draft->id]);
        Livewire::test(StructureType::class)
            ->set('type', 'hotel')
            ->call('next')
            ->assertHasNoErrors();
        $draft->refresh()->update(['rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']]]);
        $this->assertInstanceOf(Structure::class, app(DraftPublisher::class)->publish($draft->fresh()));
        // La ripubblicazione da sola deve togliere l'evento (review del
        // 28/09/2026: senza questa riga il test passava anche senza, grazie
        // alla sola eliminazione qui sotto).
        $this->assertFalse(
            Event::withHidden()->where('structure_draft_id', $draft->id)->exists(),
            'publish() deve togliere la riga della famiglia vecchia.',
        );

        // Poi elimina il servizio da «I miei servizi».
        Livewire::test(DeleteServiceModal::class)
            ->call('open', $draft->id)
            ->call('delete');

        $this->assertSame(0, Structure::withHidden()->count());
        $this->assertSame(
            0,
            Event::withHidden()->count(),
            'L\'evento pubblicato prima del cambio di famiglia è rimasto a catalogo, orfano e prenotabile: '
            .'né la ripubblicazione né l\'eliminazione lo toccano.',
        );
    }

    /**
     * Review del 28/09/2026: la riga della famiglia vecchia con una
     * prenotazione futura non si cancella. Cancellata, la prenotazione spariva
     * da «Prenotazioni» del partner (che la legge attraverso la riga) e il
     * dettaglio col contatto del cliente rispondeva 403. Si ritira dalla
     * vetrina, e resta leggibile a chi deve onorarla.
     */
    public function test_la_riga_vecchia_con_una_prenotazione_futura_si_ritira_invece_di_sparire(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sagra del cane'],
            'description' => ['it' => 'Una sagra a sei zampe.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-12-01',
            'cancellation_when' => '1',
        ]);
        $event = app(DraftPublisher::class)->publish($draft);
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        OrderItem::factory()->for($order)->create([
            'purchasable_type' => 'event',
            'purchasable_id' => $event->id,
            'booked_from' => now()->addDays(10),
            'booked_until' => now()->addDays(10),
        ]);

        session(['structure_draft_id' => $draft->id]);
        Livewire::test(StructureType::class)->set('type', 'hotel')->call('next')->assertHasNoErrors();
        $draft->refresh()->update(['rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']]]);
        app(DraftPublisher::class)->publish($draft->fresh());

        $old = Event::withHidden()->find($event->id);
        $this->assertNotNull($old, 'Con una prenotazione futura la riga non si cancella.');
        $this->assertNotNull($old->withheld_at, 'Si ritira dalla vetrina.');
        $this->assertNull(Event::query()->find($event->id), 'E il sito non la mostra più.');
    }
}
