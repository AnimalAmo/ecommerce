<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\Region\Province;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructurePublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    private function hotelDraft(array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort', 'en' => 'Bau Resort Hotel'],
            'description' => ['it' => 'Hotel pet friendly sul lago.', 'en' => 'Pet friendly hotel on the lake.'],
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'province' => 'BS',
            'zip' => '25100',
            'rooms' => [
                ['type' => 'doppia', 'count' => 3, 'price' => '80'],
                ['type' => 'singola', 'count' => 2, 'price' => '55.50'],
            ],
            'checkin_from' => '14:00',
            'checkin_to' => '20:00',
            'checkout_from' => '08:00',
            'checkout_to' => '10:00',
            'cancellation_when' => '7',
            'services' => ['wifi', 'sauna', 'tv'],
            'additional_services' => ['colazione', 'pranzo'],
            'meal_times' => [
                'colazione' => ['from' => '07:30', 'to' => '10:00'],
                'pranzo' => ['from' => '12:30', 'to' => '14:30'],
                'cena' => ['from' => '', 'to' => ''],
            ],
            'animal_services' => ['pet_sitting', 'omaggio'],
            'photos' => ['structure-photos/cover.jpg', 'structure-photos/extra.jpg'],
        ], $attributes));
    }

    public function test_publish_creates_the_catalog_structure_from_the_draft(): void
    {
        $draft = $this->hotelDraft();

        $structure = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Structure::class, $structure);
        $this->assertSame($draft->user_id, $structure->user_id);
        $this->assertSame($draft->id, $structure->structure_draft_id);
        $this->assertSame('structure', $structure->type->value);
        $this->assertSame('hotel-bau-resort-'.$draft->id, $structure->slug);
        $this->assertSame('Brescia (BS), Italia', $structure->location);
        $this->assertSame(7, $structure->cancellation_policy_days);
        $this->assertNull($structure->rating);

        // Traduzioni passate al catalogo, non stringhe raw.
        $this->assertSame('Hotel Bau Resort', $structure->getTranslation('name', 'it'));
        $this->assertSame('Bau Resort Hotel', $structure->getTranslation('name', 'en'));
        $this->assertSame('Pet friendly hotel on the lake.', $structure->getTranslation('description', 'en'));

        // Regione derivata dalla provincia (BS → Lombardia via ISTAT).
        $this->assertSame(
            Province::where('short_name', 'BS')->value('region_id'),
            $structure->region_id,
        );
        $this->assertNotNull($structure->region_id);

        // "A partire da" = stanza più economica in cents.
        $this->assertSame(5550, $structure->price_cents);
        $this->assertSame(5550, $structure->price_from_cents);
        $this->assertSame(0, $structure->animal_supplement_cents);

        // Foto wizard risolta da HasCatalogImages come URL storage; mappa assente.
        $this->assertSame('structure-photos/cover.jpg', $structure->img);
        $this->assertStringContainsString('storage/structure-photos/cover.jpg', $structure->imageUrl());
        $this->assertNull($structure->mapImageUrl());
        $this->assertNull($structure->features);
    }

    public function test_publish_synthesizes_general_info_rows(): void
    {
        $structure = app(DraftPublisher::class)->publish($this->hotelDraft());

        $icons = array_column($structure->general_info, 'icon');
        $this->assertSame(['calendar-return', 'coffee', 'lunch', 'home'], $icons);

        [$cancellation, $breakfast, $lunch, $checkin] = $structure->general_info;
        $this->assertSame(["Fino a 7 giorni prima dell'arrivo"], $cancellation['lines']);
        $this->assertSame('Colazione inclusa', $breakfast['title']);
        $this->assertSame(['Orario: 07:30-10:00'], $breakfast['lines']);
        // Solo pranzo selezionato (niente cena): titolo singolo.
        $this->assertSame('Pranzo incluso', $lunch['title']);
        $this->assertSame(['Orario: 12:30-14:30'], $lunch['lines']);
        $this->assertSame(['Check-in: 14:00-20:00', 'Check-out: 08:00-10:00'], $checkin['lines']);
    }

    public function test_publish_syncs_amenities_with_included_semantics(): void
    {
        $structure = app(DraftPublisher::class)->publish($this->hotelDraft());

        $hotel = collect($structure->amenityRows('hotel'));
        $animal = collect($structure->amenityRows('animal'));

        // Selezionate nel wizard ⇒ ✓ (sauna approssimata su Spa, pranzo dagli additional).
        $this->assertTrue($hotel->firstWhere('label', 'Wifi')['included']);
        $this->assertTrue($hotel->firstWhere('label', 'Spa')['included']);
        $this->assertTrue($hotel->firstWhere('label', 'Pranzo')['included']);
        $this->assertTrue($animal->firstWhere('label', 'Pet sitting')['included']);
        $this->assertTrue($animal->firstWhere('label', 'Omaggio di benvenuto')['included']);

        // Il resto del gruppo ⇒ ✗ (righe rosse del template).
        $this->assertFalse($hotel->firstWhere('label', 'Aria condizionata negli spazi comuni')['included']);
        $this->assertFalse($animal->firstWhere('label', 'Servizio veterinario')['included']);

        // Tutte le amenity dei due gruppi sono presenti (7 + 7 dal seeder).
        $this->assertCount(7, $hotel);
        $this->assertCount(7, $animal);
    }

    public function test_publish_skips_drafts_without_the_minimum_viable_data(): void
    {
        // Gli step finali del wizard sono URL pubblici: un draft può arrivare
        // "completed" senza nome o stanze — non deve finire sul B2C.
        $blank = StructureDraft::create(['status' => StructureDraft::STATUS_COMPLETED, 'current_step' => 11]);
        $noRooms = $this->hotelDraft(['rooms' => null]);

        $this->assertNull(app(DraftPublisher::class)->publish($blank));
        $this->assertNull(app(DraftPublisher::class)->publish($noRooms));
        $this->assertSame(0, Structure::count());
    }

    public function test_republishing_updates_the_same_row(): void
    {
        $publisher = app(DraftPublisher::class);
        $draft = $this->hotelDraft();

        $first = $publisher->publish($draft);

        $draft->setTranslation('name', 'it', 'Hotel Bau Deluxe');
        $draft->rooms = [['type' => 'suite', 'count' => 1, 'price' => '120']];
        $draft->save();

        $second = $publisher->publish($draft->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Structure::count());
        $this->assertSame('Hotel Bau Deluxe', $second->getTranslation('name', 'it'));
        $this->assertSame(12000, $second->price_cents);
        // La posizione nel listing resta stabile alla ri-pubblicazione.
        $this->assertSame($first->position, $second->position);
    }
}
