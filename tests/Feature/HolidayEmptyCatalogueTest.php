<?php

namespace Tests\Feature;

use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Giorno 1 su animalamo.it: il catalogo mock XD non si semina più, quindi
 * Animal Holiday parte con zero strutture. Le pagine devono restare oneste —
 * niente badge con numeri inventati, niente "risultati simili" sopra una
 * griglia vuota, niente paginazione finta, nessuna colpa data a filtri che il
 * visitatore non ha toccato — e la prima struttura pubblicata da un partner
 * deve comparire SOLO nella sua regione.
 */
class HolidayEmptyCatalogueTest extends TestCase
{
    use RefreshDatabase;

    /** Frammento del path di <flux:icon.star-fill> (star-mid ha 8.421, non 8.362). */
    private const FILLED_STAR = 'M17.354,8.362H12.2';

    private function region(string $name, string $slug, int $position, int $frozenCount = 0): Region
    {
        return Region::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'position' => $position,
            'img' => 'holiday-'.$slug,
            // La colonna del mock XD: resta a database, non deve più finire sul badge.
            'structures_count' => $frozenCount,
        ]);
    }

    public function test_listing_hides_the_structures_badge_when_nobody_published_yet(): void
    {
        $this->region('Liguria', 'liguria', 1, frozenCount: 158);

        $response = $this->get('/animal-holiday')->assertOk();

        // Il badge non deve mostrare né il numero congelato né uno zero.
        $response->assertDontSee('158 Strutture');
        $response->assertDontSee('0 Strutture');
        // La card della regione resta: è l'unica strada verso la pagina regione.
        $response->assertSee('Hotel e servizi in Liguria');
        // Al posto dei numeri finti, una riga onesta con una via d'uscita.
        $response->assertSee('Non ci sono ancora strutture pubblicate');
        $response->assertSee('Pubblica la tua struttura');
    }

    public function test_listing_badge_counts_the_structures_actually_published(): void
    {
        $liguria = $this->region('Liguria', 'liguria', 1, frozenCount: 158);
        Structure::factory()->create(['name' => 'Hotel Bagnasco', 'region_id' => $liguria->id]);

        $response = $this->get('/animal-holiday')->assertOk();

        $response->assertSee('1 Struttura');
        $response->assertDontSee('158 Strutture');
        // Con qualcosa a catalogo la riga "catalogo vuoto" sparisce.
        $response->assertDontSee('Non ci sono ancora strutture pubblicate');
    }

    public function test_region_page_with_an_empty_catalogue_hides_similar_results_and_pagination(): void
    {
        $this->region('Liguria', 'liguria', 1);

        $response = $this->get('/animal-holiday/liguria')->assertOk();

        // Nessun titolo "Risultati simili" sopra una griglia senza card.
        $response->assertDontSee('Risultati simili alla tua ricerca:');
        // Nessuna paginazione finta e nessun "Carica altro" che non carica niente.
        $response->assertDontSee('Paginazione');
        $response->assertDontSee('Carica altro');
        // Nessun rosso d'errore e nessuna colpa a filtri mai toccati.
        $response->assertDontSee('Nessun risultato trovato');
        $response->assertDontSee('Prova a modificare i filtri per trovare altri risultati.');
        // Al suo posto, la verità più una CTA che porta dove c'è contenuto.
        $response->assertSee('Leggi Animal Times');
    }

    public function test_a_published_structure_is_listed_only_in_its_own_region(): void
    {
        $liguria = $this->region('Liguria', 'liguria', 1);
        $this->region('Veneto', 'veneto', 2);

        Structure::factory()->create([
            'name' => 'Hotel Bagnasco',
            'slug' => 'hotel-bagnasco',
            'location' => 'Genova, Italia',
            'region_id' => $liguria->id,
        ]);

        $this->get('/animal-holiday/liguria')->assertOk()->assertSee('Hotel Bagnasco');

        $this->get('/animal-holiday/veneto')
            ->assertOk()
            ->assertDontSee('Hotel Bagnasco')
            ->assertSee('Leggi Animal Times');
    }

    public function test_region_page_still_blames_the_filters_when_the_visitor_narrowed_them(): void
    {
        $liguria = $this->region('Liguria', 'liguria', 1);
        Structure::factory()->create([
            'name' => 'Hotel Bagnasco',
            'price_from_cents' => 0,
            'region_id' => $liguria->id,
        ]);

        // Fascia di prezzo spostata dal visitatore: qui il messaggio sui filtri è corretto,
        // e le card "simili" tornano perché in regione qualcosa c'è.
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'liguria'])
            ->set('priceMin', 400)
            ->assertSee('Nessun risultato trovato')
            ->assertSee('Risultati simili alla tua ricerca:')
            ->assertSee('Hotel Bagnasco')
            ->assertDontSee('In Liguria non c’è ancora niente da prenotare');
    }

    public function test_structure_detail_without_reviews_shows_no_filled_stars(): void
    {
        $liguria = $this->region('Liguria', 'liguria', 1);

        Structure::factory()->create([
            'name' => 'Hotel Bagnasco',
            'slug' => 'hotel-bagnasco',
            // rating 0.0 (non null): range(1, 0) disegnava DUE stelle piene.
            'rating' => 0.0,
            'region_id' => $liguria->id,
        ]);

        $response = $this->get('/animal-holiday/liguria/hotel-bagnasco')->assertOk();

        $response->assertSee('Non ci sono ancora recensioni');
        $this->assertStringNotContainsString(self::FILLED_STAR, $response->getContent());
    }

    public function test_service_detail_without_reviews_shows_no_filled_stars(): void
    {
        $liguria = $this->region('Liguria', 'liguria', 1);

        Structure::factory()->service()->create([
            'name' => 'Dog sitting Genova',
            'slug' => 'dog-sitting-genova',
            'rating' => 0.0,
            'region_id' => $liguria->id,
        ]);

        $response = $this->get('/animal-holiday/liguria/servizi/dog-sitting-genova')->assertOk();

        $response->assertSee('Non ci sono ancora recensioni');
        $this->assertStringNotContainsString(self::FILLED_STAR, $response->getContent());
    }
}
