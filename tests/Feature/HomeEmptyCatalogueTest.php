<?php

namespace Tests\Feature;

use App\Livewire\Catalog\HomePage;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use App\Support\Format;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Home onesta a catalogo vuoto (go-live animalamo.it).
 *
 * Il mock XD non è più seminato in produzione: senza eventi, senza strutture e
 * senza post la home mostrava comunque numeri e testi inventati (il badge
 * "10 Strutture" della colonna congelata, il post firmato "- Sofia" del
 * 25/11/23) e una griglia eventi vuota sotto al titolo. Qui verifichiamo le due
 * facce: a database vuoto niente contenuti finti né gusci vuoti, con contenuti
 * veri le tre fasce si riempiono con i valori reali.
 */
class HomeEmptyCatalogueTest extends TestCase
{
    use RefreshDatabase;

    /** Regione in home con la colonna congelata a 10: il badge non deve fidarsene. */
    private function regionOnHome(): Region
    {
        return Region::factory()->create([
            'name' => 'Liguria',
            'slug' => 'liguria',
            'img' => 'holiday-liguria',
            'position' => 1,
            'home_position' => 1,
            'structures_count' => 10,
        ]);
    }

    public function test_empty_catalogue_home_shows_no_invented_content(): void
    {
        $this->regionOnHome();

        $html = $this->get('/')->assertOk()->getContent();

        // Il post inventato della fascia Animal Network (autore, data, citazione).
        $this->assertStringNotContainsString('Sofia', $html);
        $this->assertStringNotContainsString('25/11/23', $html);

        // Nessun badge "N Strutture": a zero strutture pubblicate non si stampa un numero.
        $this->assertDoesNotMatchRegularExpression('/\d+\s+Strutture/', $html);

        // La griglia eventi a 5 colonne: senza eventi non deve essere renderizzata affatto.
        $this->assertStringNotContainsString('grid-cols-5', $html);
    }

    public function test_real_content_fills_the_three_bands(): void
    {
        $region = $this->regionOnHome();
        Structure::factory()->count(2)->create(['region_id' => $region->id]);

        // Evento partner reale: nessun home_position (lo scrive solo il seeder del mock).
        Event::factory()->create([
            'title' => 'Trekking con i cani',
            'slug' => 'trekking-con-i-cani',
            'home_position' => null,
            'position' => 1,
            'starts_at' => now()->addWeek()->setTime(10, 0),
            'ends_at' => now()->addWeek()->setTime(12, 0),
        ]);

        $post = CommunityPost::factory()->create([
            'author_name' => 'Chiara',
            'body' => 'Consigli per un viaggio in treno con un cane di taglia media',
            'created_at' => now()->subDay(),
        ]);
        CommunityPostReply::factory()->count(3)->create(['community_post_id' => $post->id]);

        $response = $this->get('/')->assertOk();

        // Regione: conteggio vero (2), non i 10 della colonna congelata.
        $response->assertSee('2 Strutture');
        $response->assertDontSee('10 Strutture');

        // Eventi: la griglia torna, con l'evento partner (nessun home_position).
        $response->assertSee('Trekking con i cani');
        $this->assertStringContainsString('grid-cols-5', $response->getContent());

        // Animal Network: autore, data e conteggio risposte del post più recente.
        $response->assertSee('Chiara');
        $response->assertSee('Consigli per un viaggio in treno con un cane di taglia media');
        $response->assertSee('3 Risposte');
        $response->assertSee(Format::dateShort(now()->subDay()));
    }

    /**
     * Il nuovo ordinamento non svuota la home con il catalogo demo acceso: la
     * fascia resta piena, ma con soli eventi ancora proponibili (il mock XD ne
     * contiene parecchi datati 2019-2025).
     */
    public function test_demo_catalogue_still_fills_the_band_with_upcoming_events_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        Livewire::test(HomePage::class)
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->isNotEmpty() && $events->every(
                fn (Event $event): bool => $event->starts_at === null || $event->starts_at->isFuture()
            ));
    }

    /** Un evento già passato non è "in programma": la fascia resta nascosta. */
    public function test_past_events_do_not_fill_the_home_band(): void
    {
        $this->regionOnHome();

        Event::factory()->create([
            'title' => 'Raduno del 2019',
            'slug' => 'raduno-del-2019',
            'home_position' => 1,
            'starts_at' => now()->subYears(2),
            'ends_at' => now()->subYears(2)->addHours(2),
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertDontSee('Raduno del 2019');
        $this->assertStringNotContainsString('grid-cols-5', $response->getContent());
    }
}
