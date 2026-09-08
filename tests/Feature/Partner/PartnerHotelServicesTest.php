<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelServices;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_three_sections(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.services'))
            ->assertOk()
            ->assertSee(__('partner.hotel_services.heading'))
            ->assertSee(__('partner.hotel_services.step'))
            ->assertSee(__('partner.hotel_services.svc_ac'))
            ->assertSee(__('partner.hotel_services.additional_heading'))
            ->assertSee(__('partner.hotel_services.rules_heading'))
            ->assertSee(__('partner.hotel_services.rule_no_smoking'))
            ->assertSee(__('partner.hotel_services.next'));
    }

    public function test_selections_are_bound_and_next_passes(): void
    {
        Livewire::test(HotelServices::class)
            ->set('form.services', ['wifi', 'piscina'])
            ->set('form.additional', ['colazione'])
            ->set('form.structureRules', ['vietato_fumare'])
            ->assertSet('form.services', ['wifi', 'piscina'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.animal-services'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 7]);
    }

    public function test_it_rehydrates_the_saved_services(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 7,
            'services' => ['wifi'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelServices::class)->assertSet('form.services', ['wifi']);
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(HotelServices::class)
            ->assertDontSee(__('partner.hotel_services.other_placeholder'))
            ->set('form.additional', ['altro'])
            ->assertSee(__('partner.hotel_services.other_placeholder'));
    }

    public function test_meal_option_reveals_the_time_range(): void
    {
        Livewire::test(HotelServices::class)
            ->assertDontSee(__('partner.hotel_services.time_from'))
            ->set('form.additional', ['colazione'])
            ->assertSee(__('partner.hotel_services.time_from'))
            ->assertSee(__('partner.hotel_services.time_to'))
            ->set('form.mealTimes.colazione.from', '08:00')
            ->set('form.mealTimes.colazione.to', '10:00')
            ->call('next')
            ->assertHasNoErrors();
    }
}
