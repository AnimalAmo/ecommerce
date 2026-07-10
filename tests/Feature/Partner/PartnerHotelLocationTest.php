<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelLocation;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelLocationTest extends TestCase
{
    public function test_page_renders_the_location_fields(): void
    {
        $this->get(route('partner.structure.hotel.location'))
            ->assertOk()
            ->assertSee(__('partner.hotel_location.heading'))
            ->assertSee(__('partner.hotel_location.step'))
            ->assertSee(__('partner.hotel_location.address'))
            ->assertSee(__('partner.hotel_location.city'))
            ->assertSee(__('partner.hotel_location.license'))
            ->assertSee(__('partner.hotel_location.next'));
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(HotelLocation::class)
            ->call('next')
            ->assertHasErrors(['address', 'city', 'province', 'zip', 'license']);
    }

    public function test_next_rejects_a_non_numeric_zip(): void
    {
        Livewire::test(HotelLocation::class)
            ->set('zip', 'abc')
            ->call('next')
            ->assertHasErrors('zip');
    }

    public function test_next_accepts_valid_data(): void
    {
        Livewire::test(HotelLocation::class)
            ->set('address', 'Via Roma 1')
            ->set('city', 'Padova')
            ->set('province', 'PD')
            ->set('zip', '35100')
            ->set('license', 'LIC-12345')
            ->call('next')
            ->assertHasNoErrors();
    }
}
