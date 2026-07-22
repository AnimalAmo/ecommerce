<?php

namespace Tests\Feature;

use App\Models\Event\Event;
use App\Models\Structure\Structure;
use App\Models\Venue\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mappa "Dove siamo": con la chiave Google configurata i detail servono
 * l'iframe Maps Embed; senza chiave resta lo screenshot statico dell'XD
 * (phpunit.xml forza GOOGLE_MAPS_KEY vuota, come per Stripe).
 */
class CatalogGoogleMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_structure_detail_embeds_google_map_when_the_key_is_configured(): void
    {
        config(['services.google.maps_key' => 'test-key']);

        $this->get('/animal-holiday/lombardia/hotel-brescia')
            ->assertOk()
            ->assertSee('google.com/maps/embed/v1/place', false)
            ->assertSee(urlencode('Hotel Brescia, Dario Boario Terme (BS), Italia'), false);
    }

    public function test_structure_detail_keeps_the_static_screenshot_without_a_key(): void
    {
        $this->get('/animal-holiday/lombardia/hotel-brescia')
            ->assertOk()
            ->assertSee('struttura-mappa', false)
            ->assertDontSee('google.com/maps/embed', false);
    }

    public function test_event_detail_embeds_google_map_for_a_venue_with_address(): void
    {
        config(['services.google.maps_key' => 'test-key']);

        $event = Event::query()->whereHas('venue')->firstOrFail();

        $this->get('/eventi/'.$event->slug)
            ->assertOk()
            ->assertSee('google.com/maps/embed/v1/place', false);
    }

    public function test_activity_detail_embeds_google_map_for_a_venue_with_address(): void
    {
        config(['services.google.maps_key' => 'test-key']);

        $activity = Event::query()->where('type', 'activity')->whereHas('venue')->firstOrFail();

        $this->get('/eventi/attivita/'.$activity->slug)
            ->assertOk()
            ->assertSee('google.com/maps/embed/v1/place', false);
    }

    public function test_partner_items_without_screenshot_gain_a_map_only_with_the_key(): void
    {
        // Come i publisher B2B: nessuno screenshot XD, solo località/indirizzo.
        $structure = Structure::factory()->create(['map_img' => '']);
        $venue = Venue::factory()->create(['map_img' => null]);

        $this->assertFalse($structure->hasMap());
        $this->assertFalse($venue->hasMap());

        config(['services.google.maps_key' => 'test-key']);

        $this->assertTrue($structure->hasMap());
        $this->assertTrue($venue->hasMap());
    }

    public function test_name_alone_is_not_enough_for_a_map(): void
    {
        config(['services.google.maps_key' => 'test-key']);

        // Senza località/indirizzo la query cercherebbe un omonimo qualunque: sezione nascosta.
        $structure = Structure::factory()->create(['map_img' => '', 'location' => '']);
        $venue = Venue::factory()->create(['map_img' => null, 'address' => null]);

        $this->assertNull($structure->mapQuery());
        $this->assertNull($venue->mapQuery());
        $this->assertFalse($structure->hasMap());
        $this->assertFalse($venue->hasMap());
    }
}
