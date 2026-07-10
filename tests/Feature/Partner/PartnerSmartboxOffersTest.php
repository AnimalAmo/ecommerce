<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxOffers;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxOffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_both_sections(): void
    {
        $this->get(route('partner.smartbox.offers'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_offers.heading'))
            ->assertSee(__('partner.smartbox_offers.step'))
            ->assertSee(__('partner.smartbox_offers.amenity_bedroom'))
            ->assertSee(__('partner.smartbox_offers.additional_heading'))
            ->assertSee(__('partner.smartbox_offers.add_pool'));
    }

    public function test_next_saves_the_selected_options_and_advances_step(): void
    {
        Livewire::test(SmartboxOffers::class)
            ->set('amenities', ['camera_da_letto', 'cucina'])
            ->set('additional', ['piscina', 'spa'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.included'));

        $draft = StructureDraft::first();
        $this->assertSame(['camera_da_letto', 'cucina'], $draft->services);
        $this->assertSame(['piscina', 'spa'], $draft->additional_services);
        $this->assertSame(7, $draft->current_step);
    }

    public function test_it_accepts_an_empty_selection(): void
    {
        Livewire::test(SmartboxOffers::class)
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 7]);
    }

    public function test_it_rehydrates_the_saved_options(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 7,
            'services' => ['bagno', 'terrazzo'],
            'additional_services' => ['campo_da_tennis'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxOffers::class)
            ->assertSet('amenities', ['bagno', 'terrazzo'])
            ->assertSet('additional', ['campo_da_tennis']);
    }
}
