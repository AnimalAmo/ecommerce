<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelSmartbox;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelSmartboxTest extends TestCase
{
    public function test_page_renders_the_consent_and_types(): void
    {
        $this->get(route('partner.structure.hotel.smartbox'))
            ->assertOk()
            ->assertSee(__('partner.hotel_smartbox.heading'))
            ->assertSee(__('partner.hotel_smartbox.step'))
            ->assertSee(__('partner.hotel_smartbox.yes'))
            ->assertSee(__('partner.hotel_smartbox.types_heading'))
            ->assertSee(__('partner.hotel_smartbox.type_wellness'))
            ->assertSee(__('partner.hotel_smartbox.next'));
    }

    public function test_choosing_no_hides_the_type_selection(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->assertSet('consent', 'si')
            ->assertSee(__('partner.hotel_smartbox.types_heading'))
            ->set('consent', 'no')
            ->assertDontSee(__('partner.hotel_smartbox.types_heading'));
    }

    public function test_rejects_an_invalid_consent(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->set('consent', 'forse')
            ->call('next')
            ->assertHasErrors('consent');
    }

    public function test_accepts_consent_with_types(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->set('consent', 'si')
            ->set('types', ['tutta', 'benessere'])
            ->call('next')
            ->assertHasNoErrors();
    }
}
