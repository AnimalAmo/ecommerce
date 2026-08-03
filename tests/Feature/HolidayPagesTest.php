<?php

namespace Tests\Feature;

use App\Livewire\Catalog\AnimalHolidayRegion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_renders_regions_and_events_from_the_database(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hotel e servizi in Liguria')
            ->assertSee('10 Strutture')
            ->assertSee('Passeggiata a cavallo')
            ->assertSee('Esperienza con gli asini in fattoria')
            ->assertSee('Gratis')
            ->assertSee('€ 25,00');
    }

    public function test_holiday_listing_shows_the_twenty_regions_in_grid_order(): void
    {
        $response = $this->get('/animal-holiday')->assertOk();

        $response->assertSee('Hotel e servizi in Lombardia');
        $response->assertSee('Hotel e servizi in Valle d’Aosta');

        // Lombardia (posizione 1) compare prima del Lazio (posizione 2)
        $response->assertSeeInOrder(['Hotel e servizi in Lombardia', 'Hotel e servizi in Lazio']);
    }

    public function test_region_page_lists_the_structures_with_type_badges(): void
    {
        $this->get('/animal-holiday/lombardia')
            ->assertOk()
            ->assertSee('Hotel Brescia')
            ->assertSee('Dog sitting')
            ->assertSee('Lamasu W&amp;R', false)
            ->assertSee('Dario Boario Terme (BS), Italia')
            ->assertSee("0,00\u{A0}€");
    }

    public function test_region_page_404_for_unknown_region(): void
    {
        $this->get('/animal-holiday/atlantide')->assertNotFound();
    }

    public function test_region_desktop_type_pill_extends_the_catalog_via_wire_model(): void
    {
        // La pill dropdown desktop scrive activeTypes via wire:model: accendere
        // Smartbox aggiunge i cofanetti sotto le strutture di default.
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->assertSee('Hotel Brescia')
            ->assertDontSee('Weekend di relax in Lombardia')
            ->set('activeTypes', ['hotel', 'servizi', 'smartbox'])
            ->assertSee('Hotel Brescia')
            ->assertSee('Weekend di relax in Lombardia');
    }

    public function test_region_desktop_type_pill_ignores_unknown_types_and_restores_defaults(): void
    {
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['xyz'])
            ->assertSet('activeTypes', ['hotel', 'servizi'])
            ->assertSee('Hotel Brescia');
    }

    public function test_structure_detail_shows_prices_faq_and_reviews_from_db(): void
    {
        $this->get('/animal-holiday/lombardia/hotel-brescia')
            ->assertOk()
            ->assertSee("43\u{A0}€ a notte")
            ->assertSee("43\u{A0}€ per 5 notti")
            ->assertSee("215\u{A0}€")
            ->assertSee('Cancellazione gratuita')
            ->assertSee('Camera da letto')
            ->assertSee('Giulia Rossi')
            ->assertSee('23 febbraio 2023')
            ->assertSee('4,5 stelle');
    }

    public function test_service_slug_redirects_to_the_service_page(): void
    {
        $this->get('/animal-holiday/lombardia/dog-sitting')
            ->assertRedirect('/animal-holiday/lombardia/servizi/dog-sitting');
    }

    public function test_service_detail_shows_hourly_pricing_and_home_sitting_row(): void
    {
        $this->get('/animal-holiday/lombardia/servizi/dog-sitting')
            ->assertOk()
            ->assertSee("12\u{A0}€ all’ora")
            ->assertSee("12\u{A0}€ per 6 ore")
            ->assertSee("72\u{A0}€")
            ->assertSee('Dog sitting a casa')
            ->assertSee('4,5 stelle');
    }
}
