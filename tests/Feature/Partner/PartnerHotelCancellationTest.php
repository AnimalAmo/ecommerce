<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelCancellation;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelCancellationTest extends TestCase
{
    public function test_page_renders_the_cancellation_timeline(): void
    {
        $this->get(route('partner.structure.hotel.cancellation'))
            ->assertOk()
            ->assertSee(__('partner.hotel_cancellation.heading'))
            ->assertSee(__('partner.hotel_cancellation.step'))
            ->assertSee(__('partner.hotel_cancellation.free'))
            ->assertSee(__('partner.hotel_cancellation.pays'))
            ->assertSee(__('partner.hotel_cancellation.arrival'))
            ->assertSee(__('partner.hotel_cancellation.next'));
    }

    public function test_rejects_an_invalid_window(): void
    {
        Livewire::test(HotelCancellation::class)
            ->set('when', '99')
            ->call('next')
            ->assertHasErrors('when');
    }

    public function test_accepts_a_valid_window(): void
    {
        Livewire::test(HotelCancellation::class)
            ->set('when', '7')
            ->call('next')
            ->assertHasNoErrors();
    }
}
