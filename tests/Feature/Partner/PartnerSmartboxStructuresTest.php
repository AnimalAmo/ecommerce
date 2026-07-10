<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxStructures;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxStructuresTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_structure_cards(): void
    {
        $this->get(route('partner.smartbox.structures'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_structures.heading'))
            ->assertSee(__('partner.smartbox_structures.step'))
            ->assertSee('Hotel Milano')
            ->assertSee(__('partner.smartbox_structures.load_more'));
    }

    public function test_next_saves_the_selection_and_advances(): void
    {
        Livewire::test(SmartboxStructures::class)
            ->set('structures', ['hotel_milano', 'lamasu'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.photos'));

        $this->assertSame(['hotel_milano', 'lamasu'], StructureDraft::first()->smartbox_structures);
        $this->assertDatabaseHas('structure_drafts', ['current_step' => 10]);
    }

    public function test_next_rejects_an_unknown_structure(): void
    {
        Livewire::test(SmartboxStructures::class)
            ->set('structures', ['not_a_real_hotel'])
            ->call('next')
            ->assertHasErrors('structures.0');
    }

    public function test_it_rehydrates_the_saved_selection(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 10,
            'smartbox_structures' => ['hotel_brescia'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxStructures::class)->assertSet('structures', ['hotel_brescia']);
    }
}
