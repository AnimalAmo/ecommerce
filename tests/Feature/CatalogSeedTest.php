<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\Review\Review;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Venue\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_reproduces_the_xd_mock_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Structure::count());
        $this->assertSame(9, Structure::where('type', ProductType::Structure)->count());
        $this->assertSame(3, Structure::where('type', ProductType::Service)->count());

        $this->assertSame(17, Event::count());
        $this->assertSame(12, Event::whereNotNull('position')->count());
        $this->assertSame(5, Event::whereNotNull('home_position')->count());
        $this->assertSame(4, Event::where('type', ProductType::Activity)->count());

        $this->assertSame(12, SmartboxPackage::count());
        $this->assertSame(14, Amenity::count());
        $this->assertSame(2, Venue::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Structure::count());
        $this->assertSame(17, Event::count());
        $this->assertSame(12, SmartboxPackage::count());
        $this->assertSame(14, Amenity::count());
        $this->assertSame(2, Venue::count());
        $this->assertSame(12 * 12, Review::count());
    }

    public function test_structures_carry_the_detail_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $hotel = Structure::where('slug', 'hotel-brescia')->orderBy('position')->first();

        $this->assertSame(4300, $hotel->price_cents);
        $this->assertSame(4.5, $hotel->rating);
        $this->assertCount(12, $hotel->reviews);
        $this->assertCount(5, $hotel->faqs);
        $this->assertCount(6, $hotel->amenityRows('hotel'));
        $this->assertCount(6, $hotel->amenityRows('animal'));
        $this->assertSame('Pet sitting', $hotel->amenityRows('animal')[0]['label']);

        $service = Structure::where('slug', 'dog-sitting')->first();
        $this->assertSame(ProductType::Service, $service->type);
        $this->assertSame(1200, $service->price_cents);
        $this->assertSame([], $service->amenityRows('hotel'));
        $this->assertNull($service->features);
    }

    public function test_events_derive_cta_from_free_flag_and_price(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Gratis → Partecipa
        $this->assertTrue(Event::where('slug', 'festa-pet-friendly')->first()->hasJoinCta());
        // Prezzo nullo senza flag gratis (mock XD) → comunque Partecipa
        $this->assertTrue(Event::where('slug', 'pomeriggio-addestramento')->first()->hasJoinCta());
        // A pagamento → Carrello
        $this->assertFalse(Event::where('slug', 'brunch-pet-friendly')->first()->hasJoinCta());
    }

    public function test_event_amenities_use_the_event_variant(): void
    {
        $this->seed(DatabaseSeeder::class);

        $brunch = Event::where('slug', 'brunch-pet-friendly')->first();

        $hotelLabels = array_column($brunch->amenityRows('hotel'), 'label');
        $animalLabels = array_column($brunch->amenityRows('animal'), 'label');

        $this->assertContains('Pranzo', $hotelLabels);
        $this->assertNotContains('Lavanderia', $hotelLabels);
        $this->assertSame('Dog sitter', $animalLabels[0]);
        $this->assertSame('Cascina Brescia', $brunch->venue->name);
    }
}
