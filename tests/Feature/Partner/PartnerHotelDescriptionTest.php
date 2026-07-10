<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelDescription;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelDescriptionTest extends TestCase
{
    public function test_page_renders_the_description_field(): void
    {
        $this->get(route('partner.structure.hotel.description'))
            ->assertOk()
            ->assertSee(__('partner.hotel_description.heading'))
            ->assertSee(__('partner.hotel_description.step'))
            ->assertSee(__('partner.hotel_description.section'))
            ->assertSee(__('partner.hotel_description.chars'))
            ->assertSee(__('partner.hotel_description.next'));
    }

    public function test_next_requires_a_description(): void
    {
        Livewire::test(HotelDescription::class)
            ->call('next')
            ->assertHasErrors('description');
    }

    public function test_next_rejects_a_description_over_200_chars(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description', str_repeat('a', 201))
            ->call('next')
            ->assertHasErrors('description');
    }

    public function test_next_accepts_a_valid_description(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description', 'Un accogliente hotel pet-friendly nel cuore della città.')
            ->call('next')
            ->assertHasNoErrors();
    }
}
