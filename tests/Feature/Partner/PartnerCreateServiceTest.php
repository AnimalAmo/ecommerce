<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\CreateService;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerCreateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_four_service_types(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.service.create'))
            ->assertOk()
            ->assertSee(__('partner.create_service.heading'))
            ->assertSee(__('partner.create_service.helper'))
            ->assertSee(__('partner.create_service.struttura_title'))
            ->assertSee(__('partner.create_service.attivita_title'))
            ->assertSee(__('partner.create_service.servizi_title'))
            ->assertSee(__('partner.create_service.smartbox_title'))
            ->assertSee(__('partner.create_service.next'));
    }

    public function test_next_requires_a_service(): void
    {
        Livewire::test(CreateService::class)
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_next_rejects_an_unknown_service(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'inesistente')
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_struttura_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'struttura')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'struttura']);
    }

    public function test_attivita_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'attivita']);
    }

    public function test_smartbox_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'smartbox')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'smartbox']);
    }

    public function test_servizi_goes_to_the_activity_flow_and_never_shows_the_structure_categories(): void
    {
        // Fino al 29/09/2026 "Servizi" percorreva gli step della struttura
        // ricettiva: un toelettatore o un dog sitter si vedeva chiedere hotel,
        // B&B, agriturismo o casa vacanza. La cliente ha chiesto il contrario.
        Livewire::test(CreateService::class)
            ->set('service', 'servizi')
            ->call('next')
            ->assertHasNoErrors()
            // Salta anche la scelta Attività/Evento: chi clicca "Servizi" l'ha già fatta.
            ->assertRedirect(route('partner.activity.name'));

        $this->assertDatabaseHas('structure_drafts', [
            // 'attivita' e non 'servizi': family() manda 'servizi' su
            // StructurePublisher, e una bozza compilata col wizard attività
            // pubblicata come Struttura sarebbe rotta.
            'service_category' => 'attivita',
            'type' => 'attivita',
        ]);
    }

    /**
     * Dopo una pubblicazione in attesa di Stripe l'id poteva restare in
     * sessione: "Crea servizio" riapriva quella bozza e next() ne riscriveva
     * la categoria, trasformandola nel servizio successivo.
     */
    public function test_crea_servizio_non_riprende_una_bozza_in_attesa(): void
    {
        $partner = $this->actingAsActivePartner();
        $waiting = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'publish_requested_at' => now(),
        ]);
        session(['structure_draft_id' => $waiting->id]);

        Livewire::test(CreateService::class)
            ->assertSet('service', '')
            ->set('service', 'attivita')
            ->call('next')
            ->assertRedirect(route('partner.activity.type'));

        $this->assertSame('smartbox', $waiting->fresh()->service_category);
        $this->assertNotSame($waiting->id, session('structure_draft_id'));
        $this->assertDatabaseHas('structure_drafts', ['user_id' => $partner->id, 'service_category' => 'attivita']);
    }

    /** Dal menu "Crea servizio" durante una modifica: il servizio pubblicato non si riscrive. */
    public function test_crea_servizio_non_riprende_un_servizio_in_modifica(): void
    {
        $partner = $this->actingAsActivePartner();
        $published = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
        ]);
        session(['structure_draft_id' => $published->id]);

        Livewire::test(CreateService::class)
            ->assertSet('service', '')
            ->set('service', 'smartbox')
            ->call('next');

        $this->assertSame('struttura', $published->fresh()->service_category);
        $this->assertNotSame($published->id, session('structure_draft_id'));
    }

    /**
     * Refresh della pagina o "Indietro" dallo step del tipo: si riprende la
     * bozza appena iniziata, con la scelta fatta, senza una riga per visita.
     */
    public function test_una_bozza_appena_iniziata_si_riprende_senza_righe_nuove(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(CreateService::class);
        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next');

        Livewire::test(CreateService::class)->assertSet('service', 'attivita');

        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }
}
