<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityType;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Structure\StructureDraft;
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
