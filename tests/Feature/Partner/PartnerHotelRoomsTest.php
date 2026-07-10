<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelRooms;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelRoomsTest extends TestCase
{
    public function test_page_renders_the_room_fields(): void
    {
        $this->get(route('partner.structure.hotel.rooms'))
            ->assertOk()
            ->assertSee(__('partner.hotel_rooms.heading'))
            ->assertSee(__('partner.hotel_rooms.step'))
            ->assertSee(__('partner.hotel_rooms.room_type'))
            ->assertSee(__('partner.hotel_rooms.add_rooms'))
            ->assertSee(__('partner.hotel_rooms.checkin'))
            ->assertSee(__('partner.hotel_rooms.checkout'))
            ->assertSee(__('partner.hotel_rooms.next'));
    }

    public function test_add_room_appends_a_row(): void
    {
        Livewire::test(HotelRooms::class)
            ->assertCount('rooms', 1)
            ->call('addRoom')
            ->assertCount('rooms', 2);
    }

    public function test_stepper_increments_and_decrements_without_going_negative(): void
    {
        Livewire::test(HotelRooms::class)
            ->call('incrementRoom', 0)
            ->call('incrementRoom', 0)
            ->assertSet('rooms.0.count', 2)
            ->call('decrementRoom', 0)
            ->assertSet('rooms.0.count', 1)
            ->call('decrementRoom', 0)
            ->call('decrementRoom', 0)
            ->assertSet('rooms.0.count', 0);
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(HotelRooms::class)
            ->call('next')
            ->assertHasErrors(['rooms.0.type', 'rooms.0.count', 'rooms.0.price', 'checkinFrom', 'checkoutTo']);
    }

    public function test_next_accepts_valid_data(): void
    {
        Livewire::test(HotelRooms::class)
            ->set('rooms.0.type', 'doppia')
            ->set('rooms.0.count', 3)
            ->set('rooms.0.price', '80')
            ->set('checkinFrom', '14:00')
            ->set('checkinTo', '20:00')
            ->set('checkoutFrom', '08:00')
            ->set('checkoutTo', '11:00')
            ->call('next')
            ->assertHasNoErrors();
    }
}
