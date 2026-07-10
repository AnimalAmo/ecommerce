<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelTitle;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelTitleTest extends TestCase
{
    public function test_page_renders_the_name_field(): void
    {
        $this->get(route('partner.structure.hotel.title'))
            ->assertOk()
            ->assertSee(__('partner.hotel_title.heading'))
            ->assertSee(__('partner.hotel_title.step'))
            ->assertSee(__('partner.hotel_title.field_label'))
            ->assertSee(__('partner.hotel_title.next'));
    }

    public function test_next_requires_a_name(): void
    {
        Livewire::test(HotelTitle::class)
            ->call('next')
            ->assertHasErrors('name');
    }

    public function test_next_accepts_a_name(): void
    {
        Livewire::test(HotelTitle::class)
            ->set('name', 'Hotel Bau Resort')
            ->call('next')
            ->assertHasNoErrors();
    }
}
