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

    public function test_every_family_panel_renders_its_own_table(): void
    {
        $this->actingAsActivePartner();

        // I 4 flux:tab.panel sono tutti nel DOM (Flux mostra quello attivo):
        // ogni famiglia porta la sua tabella con le sue righe.
        Livewire::test(PartnerBookings::class)
            ->assertSee('Hotel Brescia')
            ->assertSee('Puppy Yoga')
            ->assertSee('Vacanza di relax in montagna')
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee(__('partner.bookings.col_validity'))
            ->assertSee(__('partner.bookings.col_time'));
    }

    public function test_family_columns_follow_the_mockup(): void
    {
        $component = new PartnerBookings;

        // Eventi: Ora e niente Prezzo; smartbox: Validità e niente Data/N. Persone.
        $this->assertArrayHasKey('time', $component->columnsFor('eventi'));
        $this->assertArrayNotHasKey('price', $component->columnsFor('eventi'));
        $this->assertSame('col_validity', $component->columnsFor('smartbox')['date']);
        $this->assertArrayNotHasKey('people', $component->columnsFor('smartbox'));
        $this->assertSame('col_structure', $component->columnsFor('strutture')['title']);
        $this->assertSame('col_activity', $component->columnsFor('attivita')['title']);
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
