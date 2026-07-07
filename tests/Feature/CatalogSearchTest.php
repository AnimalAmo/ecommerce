<?php

namespace Tests\Feature;

use App\Livewire\Catalog\AnimalHoliday;
use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\HomePage;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use App\Models\Venue\Venue;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    // ============ A) Filtro "Dove" ============

    public function test_events_filter_by_dove_matches_title_location_or_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Arena di Verona']);

        Event::factory()->create(['title' => 'Brunch a Roma', 'location' => 'Roma, Italia']);
        Event::factory()->create(['title' => 'Concerto', 'location' => 'Verona, Italia', 'venue_id' => $venue->id]);
        Event::factory()->create(['title' => 'Trekking', 'location' => 'Milano, Italia']);

        // Match su location
        Livewire::test(Events::class)
            ->set('where', 'Roma')
            ->assertSee('Brunch a Roma')
            ->assertDontSee('Concerto')
            ->assertDontSee('Trekking');

        // Match sul nome della venue (whereHas)
        Livewire::test(Events::class)
            ->set('where', 'Arena')
            ->assertSee('Concerto')
            ->assertDontSee('Brunch a Roma');

        // Match sul titolo
        Livewire::test(Events::class)
            ->set('where', 'Trekking')
            ->assertSee('Trekking')
            ->assertDontSee('Brunch a Roma');
    }

    public function test_events_filter_is_case_insensitive_and_trimmed(): void
    {
        Event::factory()->create(['title' => 'Evento uno', 'location' => 'Bologna, Italia']);
        Event::factory()->create(['title' => 'Evento due', 'location' => 'Napoli, Italia']);

        Livewire::test(Events::class)
            ->set('where', '  bOLOGna ')
            ->assertSee('Evento uno')
            ->assertDontSee('Evento due');
    }

    public function test_events_search_resets_pagination_to_page_one(): void
    {
        // 20 eventi a Torino → 2 pagine (PER_PAGE = 12); il filtro deve riportare a pagina 1.
        Event::factory()->count(20)->sequence(fn ($sequence) => [
            'title' => 'Torino evento '.$sequence->index,
            'location' => 'Torino, Italia',
        ])->create();

        Livewire::test(Events::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('where', 'Torino')
            ->call('search')
            ->assertSet('paginators.page', 1);
    }

    public function test_animal_holiday_filters_regions_by_dove_query_param(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Region::factory()->create(['name' => 'Lazio', 'slug' => 'lazio', 'position' => 2]);

        Livewire::withQueryParams(['dove' => 'Lombardia'])
            ->test(AnimalHoliday::class)
            ->assertSee('Lombardia')
            ->assertDontSee('Lazio');
    }

    public function test_animal_holiday_shows_empty_state_when_no_region_matches(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);

        Livewire::test(AnimalHoliday::class)
            ->set('where', 'Atlantide')
            ->assertSee('Nessuna località trovata')
            ->assertDontSee('Lombardia');
    }

    public function test_animal_holiday_shows_all_regions_without_filter(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Region::factory()->create(['name' => 'Lazio', 'slug' => 'lazio', 'position' => 2]);

        Livewire::test(AnimalHoliday::class)
            ->assertSee('Lombardia')
            ->assertSee('Lazio');
    }

    public function test_region_page_keeps_all_structures_when_dove_equals_region_name(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Alfa', 'location' => 'Milano, Italia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Beta', 'location' => 'Bergamo, Italia', 'position' => 2]);

        // where pre-compilato col nome regione → mostra tutte le strutture mock.
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->assertSet('where', 'Lombardia')
            ->assertSee('Hotel Alfa')
            ->assertSee('Hotel Beta');
    }

    public function test_region_page_filters_structures_when_user_changes_dove(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Alfa', 'location' => 'Milano, Italia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Beta', 'location' => 'Bergamo, Italia', 'position' => 2]);

        // Filtro per location
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('where', 'Milano')
            ->call('search')
            ->assertSee('Hotel Alfa')
            ->assertDontSee('Hotel Beta');

        // Filtro per nome
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('where', 'Beta')
            ->call('search')
            ->assertSee('Hotel Beta')
            ->assertDontSee('Hotel Alfa');
    }

    public function test_home_search_redirects_to_holiday_with_dove_query(): void
    {
        Livewire::test(HomePage::class)
            ->set('where', 'Lombardia')
            ->call('search')
            ->assertRedirect(route('holiday', ['dove' => 'Lombardia']));
    }

    public function test_home_search_carries_datepicker_dates_in_redirect(): void
    {
        $checkIn = new DateTimeImmutable('+7 days');
        $checkOut = new DateTimeImmutable('+9 days');

        Livewire::test(HomePage::class)
            ->set('where', 'Lazio')
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->call('search')
            ->assertRedirect(route('holiday', [
                'dove' => 'Lazio',
                'checkin' => $checkIn->format('d/m/Y'),
                'checkout' => $checkOut->format('d/m/Y'),
            ]));
    }

    // ============ B) Datepicker "Quando" ============

    public function test_home_datepicker_selects_check_in_and_check_out_range(): void
    {
        $checkIn = new DateTimeImmutable('+3 days');
        $checkOut = new DateTimeImmutable('+6 days');

        Livewire::test(HomePage::class)
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', null)
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', $checkOut->format('d/m/Y'));
    }

    public function test_events_datepicker_selects_range_without_filtering_list(): void
    {
        Event::factory()->create(['title' => 'Evento datato', 'location' => 'Pisa, Italia']);

        $checkIn = new DateTimeImmutable('+3 days');
        $checkOut = new DateTimeImmutable('+5 days');

        // Il "Quando" raccoglie le date ma NON filtra la lista (step disponibilità).
        Livewire::test(Events::class)
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', $checkOut->format('d/m/Y'))
            ->assertSee('Evento datato');
    }
}
