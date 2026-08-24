<?php

namespace Tests\Feature\Partner;

use App\Livewire\Forms\HotelRoomsForm;
use App\Livewire\Partner\Structure\HotelRooms;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelRoomsTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertCount('form.rooms', 1)
            ->call('addRoom')
            ->assertCount('form.rooms', 2);
    }

    public function test_stepper_increments_and_decrements_without_going_negative(): void
    {
        Livewire::test(HotelRooms::class)
            ->call('incrementRoom', 0)
            ->call('incrementRoom', 0)
            ->assertSet('form.rooms.0.count', 2)
            ->call('decrementRoom', 0)
            ->assertSet('form.rooms.0.count', 1)
            ->call('decrementRoom', 0)
            ->call('decrementRoom', 0)
            ->assertSet('form.rooms.0.count', 0);
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(HotelRooms::class)
            ->call('next')
            ->assertHasErrors(['form.rooms.0.type', 'form.rooms.0.count', 'form.rooms.0.price', 'form.checkinFrom', 'form.checkoutTo']);
    }

    public function test_next_accepts_valid_data(): void
    {
        Livewire::test(HotelRooms::class)
            ->set('form.rooms.0.type', 'doppia')
            ->set('form.rooms.0.count', 3)
            ->set('form.rooms.0.price', '80')
            ->set('form.checkinFrom', '14:00')
            ->set('form.checkinTo', '20:00')
            ->set('form.checkoutFrom', '08:00')
            ->set('form.checkoutTo', '11:00')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.cancellation'));

        $this->assertDatabaseHas('structure_drafts', ['checkin_from' => '14:00', 'current_step' => 5]);
    }

    public function test_it_rehydrates_the_saved_checkin_from(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'checkin_from' => '15:00']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelRooms::class)->assertSet('form.checkinFrom', '15:00');
    }

    /**
     * Casa vacanza: si affitta l'alloggio intero, quindi lo step perde le
     * righe stanza ripetibili e chiede posti letto + prezzo a notte.
     */
    private function wholePropertyDraft(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'type' => 'casa_vacanza']);
        session(['structure_draft_id' => $draft->id]);
    }

    public function test_whole_property_starts_with_a_single_locked_unit(): void
    {
        $this->wholePropertyDraft();

        Livewire::test(HotelRooms::class)
            ->assertSet('form.wholeProperty', true)
            ->assertCount('form.rooms', 1)
            ->assertSet('form.rooms.0.count', 1)
            ->assertSet('form.rooms.0.type', HotelRoomsForm::WHOLE_PROPERTY_TYPE);
    }

    public function test_whole_property_ignores_the_room_repeater_controls(): void
    {
        $this->wholePropertyDraft();

        Livewire::test(HotelRooms::class)
            ->call('addRoom')
            ->assertCount('form.rooms', 1)
            ->call('incrementRoom', 0)
            ->assertSet('form.rooms.0.count', 1)
            ->call('decrementRoom', 0)
            ->assertSet('form.rooms.0.count', 1);
    }

    public function test_whole_property_page_swaps_the_room_copy_for_the_unit_copy(): void
    {
        $this->wholePropertyDraft();

        $this->get(route('partner.structure.hotel.rooms'))
            ->assertOk()
            ->assertSee(__('partner.hotel_rooms.whole_heading'))
            ->assertSee(__('partner.hotel_rooms.beds'))
            ->assertDontSee(__('partner.hotel_rooms.add_rooms'))
            ->assertDontSee(__('partner.hotel_rooms.room_count'));
    }

    public function test_whole_property_requires_the_bed_count(): void
    {
        $this->wholePropertyDraft();

        Livewire::test(HotelRooms::class)
            ->set('form.rooms.0.price', '120')
            ->set('form.checkinFrom', '15:00')
            ->set('form.checkinTo', '20:00')
            ->set('form.checkoutFrom', '08:00')
            ->set('form.checkoutTo', '10:00')
            ->call('next')
            ->assertHasErrors('form.rooms.0.beds');
    }

    public function test_whole_property_saves_beds_and_advances(): void
    {
        $this->wholePropertyDraft();

        Livewire::test(HotelRooms::class)
            ->set('form.rooms.0.beds', 6)
            ->set('form.rooms.0.price', '120')
            ->set('form.checkinFrom', '15:00')
            ->set('form.checkinTo', '20:00')
            ->set('form.checkoutFrom', '08:00')
            ->set('form.checkoutTo', '10:00')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.cancellation'));

        $rooms = StructureDraft::latest('id')->first()->rooms;

        $this->assertSame(HotelRoomsForm::WHOLE_PROPERTY_TYPE, $rooms[0]['type']);
        $this->assertSame(1, $rooms[0]['count']);
        $this->assertSame(6, $rooms[0]['beds']);
    }
}
