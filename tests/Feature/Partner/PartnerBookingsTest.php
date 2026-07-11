<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Bookings\PartnerBookings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away(): void
    {
        $this->get(route('partner.bookings'))->assertRedirect();
    }

    public function test_page_renders_the_structures_tab_by_default(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.bookings'))
            ->assertOk()
            ->assertSee(__('partner.bookings.heading'))
            ->assertSee(__('partner.bookings.tab_strutture'))
            ->assertSee(__('partner.bookings.col_id'))
            ->assertSee(__('partner.bookings.col_structure'))
            ->assertSee('BD94KEU9E')
            ->assertSee('Hotel Brescia')
            ->assertSee('215€');
    }

    public function test_tabs_swap_the_family_columns(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerBookings::class)
            ->set('tab', 'eventi')
            ->assertSee(__('partner.bookings.col_event'))
            ->assertSee(__('partner.bookings.col_time'))
            ->assertSee('Puppy Yoga')
            ->assertDontSee(__('partner.bookings.col_price'))
            ->set('tab', 'smartbox')
            ->assertSee(__('partner.bookings.col_validity'))
            ->assertSee('Weekend di relax in Lombardia')
            ->assertDontSee(__('partner.bookings.col_people'));
    }

    public function test_an_unknown_tab_falls_back_to_the_first_one(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerBookings::class)
            ->set('tab', 'hacker')
            ->assertSet('tab', 'strutture');
    }

    public function test_search_filters_the_rows(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerBookings::class)
            ->set('search', 'giulia')
            ->assertSee('Hotel Brescia')
            ->set('search', 'nessun match possibile')
            ->assertDontSee('Hotel Brescia')
            ->assertSee(__('partner.bookings.empty'));
    }

    public function test_the_date_filter_keeps_only_bookings_covering_that_day(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerBookings::class)
            ->set('date', '2024-02-22')
            ->assertSee('Hotel Brescia')
            ->set('date', '2026-01-01')
            ->assertDontSee('Hotel Brescia')
            ->assertSee(__('partner.bookings.empty'));
    }
}
