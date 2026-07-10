<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelServices;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelServicesTest extends TestCase
{
    public function test_page_renders_the_three_sections(): void
    {
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
            ->set('services', ['wifi', 'piscina'])
            ->set('additional', ['colazione'])
            ->set('rules', ['vietato_fumare'])
            ->assertSet('services', ['wifi', 'piscina'])
            ->call('next')
            ->assertHasNoErrors();
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(HotelServices::class)
            ->assertDontSee(__('partner.hotel_services.other_placeholder'))
            ->set('additional', ['altro'])
            ->assertSee(__('partner.hotel_services.other_placeholder'));
    }

    public function test_meal_option_reveals_the_time_range(): void
    {
        Livewire::test(HotelServices::class)
            ->assertDontSee(__('partner.hotel_services.time_from'))
            ->set('additional', ['colazione'])
            ->assertSee(__('partner.hotel_services.time_from'))
            ->assertSee(__('partner.hotel_services.time_to'))
            ->set('mealTimes.colazione.from', '08:00')
            ->set('mealTimes.colazione.to', '10:00')
            ->call('next')
            ->assertHasNoErrors();
    }
}
