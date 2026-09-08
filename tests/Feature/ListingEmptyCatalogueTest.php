<?php

namespace Tests\Feature;

use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\Smartbox;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Giorno 1 su animalamo.it: il catalogo mock XD non è più seminato, quindi
 * /eventi e /smartbox aprono su zero righe. Le due liste devono restare oneste —
 * niente "modifica i filtri" a chi non ha toccato nessun filtro, niente
 * "risultati simili" sopra una griglia vuota — senza perdere il comportamento
 * reale del caso filtrato, che invece i filtri li ha davvero.
 */
class ListingEmptyCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Solo il seed di piattaforma (regioni, pagine legali, Animal Times):
        // è esattamente ciò che gira in produzione, catalogo escluso.
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_events_with_an_empty_catalogue_is_honest_and_offers_a_way_out(): void
    {
        $this->get('/eventi')
            ->assertOk()
            ->assertSee(__('events.empty_catalogue_title'))
            ->assertSee(__('events.empty_catalogue_body'))
            ->assertSee(__('events.empty_catalogue_news_cta'))
            ->assertSee(__('events.empty_catalogue_partner_cta'))
            // Nessuno ha filtrato niente: la colpa non è del visitatore.
            ->assertDontSee(__('catalog.no_results_title'))
            ->assertDontSee(__('catalog.no_results_hint'))
            // Non c'è nessun risultato simile da proporre.
            ->assertDontSee(__('catalog.similar_results_title'))
            // Il bottone del modal filtri non promette zero risultati.
            ->assertDontSee(__('catalog.show_results', ['count' => 0]))
            ->assertSee(__('catalog.close_filters'));
    }

    public function test_smartbox_with_an_empty_catalogue_is_honest_and_offers_a_way_out(): void
    {
        $this->get('/smartbox')
            ->assertOk()
            ->assertSee(__('smartbox.empty_catalogue_title'))
            ->assertSee(__('smartbox.empty_catalogue_body'))
            ->assertSee(__('smartbox.empty_catalogue_news_cta'))
            ->assertSee(__('smartbox.empty_catalogue_partner_cta'))
            ->assertDontSee(__('catalog.no_results_title'))
            ->assertDontSee(__('catalog.no_results_hint'))
            ->assertDontSee(__('catalog.similar_results_title'))
            ->assertDontSee(__('catalog.show_results', ['count' => 0]))
            ->assertSee(__('catalog.close_filters'));
    }

    public function test_events_listing_renders_normally_when_the_catalogue_has_rows(): void
    {
        Event::factory()->create([
            'title' => 'Camminata nel bosco',
            'slug' => 'camminata-nel-bosco',
            'position' => 1,
            'price_cents' => 30000,
        ]);

        $this->get('/eventi')
            ->assertOk()
            ->assertSee('Camminata nel bosco')
            ->assertDontSee(__('events.empty_catalogue_title'))
            ->assertDontSee(__('catalog.no_results_title'))
            ->assertDontSee(__('catalog.similar_results_title'))
            ->assertSee(__('catalog.show_results', ['count' => 1]));
    }

    public function test_smartbox_listing_renders_normally_when_the_catalogue_has_rows(): void
    {
        SmartboxPackage::factory()->create([
            'title' => 'Cofanetto di prova',
            'slug' => 'cofanetto-di-prova',
            'position' => 1,
        ]);

        $this->get('/smartbox')
            ->assertOk()
            ->assertSee('Cofanetto di prova')
            ->assertDontSee(__('smartbox.empty_catalogue_title'))
            ->assertDontSee(__('catalog.no_results_title'))
            ->assertDontSee(__('catalog.similar_results_title'))
            ->assertSee(__('catalog.show_results', ['count' => 1]));
    }

    public function test_events_filtered_to_nothing_keeps_the_filter_copy_and_similar_results(): void
    {
        // Un evento fuori dalla fascia di prezzo scelta: la griglia si svuota per
        // colpa del filtro, quindi il copy sui filtri (e le card simili) è corretto.
        Event::factory()->create([
            'title' => 'Camminata nel bosco',
            'slug' => 'camminata-nel-bosco',
            'position' => 1,
            'price_cents' => 30000,
        ]);

        Livewire::test(Events::class)
            ->set('priceMax', 10)
            ->assertSee(__('catalog.no_results_title'))
            ->assertSee(__('catalog.no_results_hint'))
            ->assertSee(__('catalog.similar_results_title'))
            ->assertSee('Camminata nel bosco')
            ->assertDontSee(__('events.empty_catalogue_title'));
    }

    public function test_smartbox_filtered_to_nothing_keeps_the_filter_copy_and_similar_results(): void
    {
        SmartboxPackage::factory()->create([
            'title' => 'Cofanetto di prova',
            'slug' => 'cofanetto-di-prova',
            'position' => 1,
            'price_from_cents' => 30000,
        ]);

        Livewire::test(Smartbox::class)
            ->set('priceMax', 10)
            ->assertSee(__('catalog.no_results_title'))
            ->assertSee(__('catalog.no_results_hint'))
            ->assertSee(__('catalog.similar_results_title'))
            ->assertSee('Cofanetto di prova')
            ->assertDontSee(__('smartbox.empty_catalogue_title'));
    }
}
