<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxStructures;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxStructuresTest extends TestCase
{
    use RefreshDatabase;

    /** Struttura pubblicata dal partner: è l'unica fonte delle card dello step. */
    private function structureOf(User $partner, string $name = 'Rifugio del Cane'): Structure
    {
        return Structure::factory()->create([
            'user_id' => $partner->id,
            'name' => $name,
            'location' => 'Bormio (SO)',
        ]);
    }

    public function test_page_renders_the_structure_cards(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $partner = $this->actingAsActivePartner();
        $this->structureOf($partner);

        $this->get(route('partner.smartbox.structures'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_structures.heading'))
            ->assertSee(__('partner.smartbox_structures.step'))
            ->assertSee('Rifugio del Cane')
            ->assertSee('Bormio (SO)');
    }

    public function test_next_saves_the_selection_and_advances(): void
    {
        $partner = $this->actingAsActivePartner();
        $first = $this->structureOf($partner, 'Rifugio del Cane');
        $second = $this->structureOf($partner, 'Baita dei Gatti');

        Livewire::test(SmartboxStructures::class)
            ->set('structures', [(string) $first->id, (string) $second->id])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.photos'));

        $this->assertSame(
            [(string) $first->id, (string) $second->id],
            StructureDraft::whereNotNull('smartbox_structures')->first()->smartbox_structures
        );
        $this->assertDatabaseHas('structure_drafts', ['current_step' => 10]);
    }

    public function test_next_rejects_an_unknown_structure(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(SmartboxStructures::class)
            ->set('structures', ['not_a_real_hotel'])
            ->call('next')
            ->assertHasErrors('structures.0');
    }

    public function test_it_rehydrates_the_saved_selection(): void
    {
        $partner = $this->actingAsActivePartner();
        $structure = $this->structureOf($partner);

        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 10,
            'smartbox_structures' => [(string) $structure->id],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxStructures::class)->assertSet('structures', [(string) $structure->id]);
    }

    /**
     * Le bozze salvate prima del passaggio alle strutture reali contengono le
     * chiavi demo del mockup ("hotel_brescia", …). Non corrispondono a nulla:
     * vanno scartate all'idratazione, altrimenti il partner si ritroverebbe
     * bloccato su un errore di validazione per un checkbox che non vede.
     */
    public function test_it_drops_legacy_mock_keys_from_a_saved_draft(): void
    {
        $partner = $this->actingAsActivePartner();
        $structure = $this->structureOf($partner);

        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 10,
            'smartbox_structures' => ['hotel_brescia', (string) $structure->id],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxStructures::class)
            ->assertSet('structures', [(string) $structure->id])
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame([(string) $structure->id], $draft->fresh()->smartbox_structures);
    }
}
