<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelAnimalServices;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelAnimalServicesTest extends TestCase
{
    public function test_page_renders_the_options(): void
    {
        $this->get(route('partner.structure.hotel.animal-services'))
            ->assertOk()
            ->assertSee(__('partner.hotel_animal_services.heading'))
            ->assertSee(__('partner.hotel_animal_services.step'))
            ->assertSee(__('partner.hotel_animal_services.opt_welcome'))
            ->assertSee(__('partner.hotel_animal_services.opt_petsitting_desc'))
            ->assertSee(__('partner.hotel_animal_services.opt_vet'))
            ->assertSee(__('partner.hotel_animal_services.next'));
    }

    public function test_selections_bind_and_next_passes(): void
    {
        Livewire::test(HotelAnimalServices::class)
            ->set('services', ['omaggio', 'area_animali'])
            ->assertSet('services', ['omaggio', 'area_animali'])
            ->call('next')
            ->assertHasNoErrors();
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(HotelAnimalServices::class)
            ->assertDontSee(__('partner.hotel_animal_services.other_placeholder'))
            ->set('services', ['altro'])
            ->assertSee(__('partner.hotel_animal_services.other_placeholder'));
    }
}
