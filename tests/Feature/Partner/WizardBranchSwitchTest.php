<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityType;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     * `ActivityType::clearedFields()` è stata estesa alle colonne del
     * 26-27/09/2026, `StructureType::clearedFields()` no: azzera le sei vecchie
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

        $draft->refresh()->update(['status' => StructureDraft::STATUS_COMPLETED, 'current_step' => 11]);

        $published = app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertNull(
            $published?->max_participants,
            'Un servizio senza capienza dichiarata non deve nascere esauribile col limite di un evento abbandonato.',
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
}
