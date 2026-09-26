<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Richiesta della cliente, 29/09/2026: sulla pagina pubblica compaiono solo le
 * caratteristiche e i servizi che la struttura offre davvero. Le voci assenti
 * — che il template disegnava con una X rossa — appesantivano la scheda e
 * davano una percezione negativa.
 *
 * Il pivot continua a registrarle: il filtro è in lettura, quindi reversibile.
 */
class AmenityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Voce offerta, comune a tutte le schede del test. */
    private const OFFERTO = 'Wifi';

    /** Voce marcata non offerta: non deve comparire da nessuna parte. */
    private const NON_OFFERTO = 'Piscina per cani';

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    /** Attacca una voce offerta e una non offerta, una per gruppo. */
    private function withMixedAmenities(Model $model): Model
    {
        $hotel = Amenity::factory()->create(['name' => self::OFFERTO, 'group' => Amenity::GROUP_HOTEL]);
        $animal = Amenity::factory()->create(['name' => self::NON_OFFERTO, 'group' => Amenity::GROUP_ANIMAL]);

        $model->amenities()->sync([
            $hotel->id => ['included' => true, 'position' => 1],
            $animal->id => ['included' => false, 'position' => 2],
        ]);

        return $model->refresh();
    }

    public function test_amenity_rows_returns_only_what_is_offered(): void
    {
        $structure = $this->withMixedAmenities(Structure::factory()->create());

        $this->assertSame([['label' => self::OFFERTO, 'included' => true]], $structure->amenityRows('hotel'));
        $this->assertSame([], $structure->amenityRows('animal'));

        // Il dato resta: si torna indietro togliendo il filtro, senza migrazioni.
        $this->assertCount(2, $structure->amenities);
    }

    public function test_the_structure_page_hides_what_is_not_offered(): void
    {
        $this->withMixedAmenities(Structure::factory()->create(['slug' => 'hotel-misto']));

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-misto'])
            ->assertOk()
            ->assertSee(self::OFFERTO)
            ->assertDontSee(self::NON_OFFERTO)
            // Il gruppo animali resta senza voci: il suo riquadro sparisce
            // invece di lasciare una cornice alta 250px col solo titolo.
            ->assertSee(__('holiday.hotel_services'))
            ->assertDontSee(__('holiday.animal_services'));
    }

    public function test_the_structure_page_drops_the_whole_section_without_amenities(): void
    {
        Structure::factory()->create(['slug' => 'hotel-spoglio']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-spoglio'])
            ->assertOk()
            ->assertDontSee(__('holiday.hotel_services'))
            ->assertDontSee(__('holiday.animal_services'));
    }

    public function test_the_service_page_hides_what_is_not_offered(): void
    {
        $service = Structure::factory()->service()->create(['slug' => 'dog-sitting-misto']);
        $offerto = Amenity::factory()->create(['name' => 'Pet sitting', 'group' => Amenity::GROUP_ANIMAL]);
        $assente = Amenity::factory()->create(['name' => self::NON_OFFERTO, 'group' => Amenity::GROUP_ANIMAL]);
        $service->amenities()->sync([
            $offerto->id => ['included' => true, 'position' => 1],
            $assente->id => ['included' => false, 'position' => 2],
        ]);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-misto'])
            ->assertOk()
            ->assertSee('Pet sitting')
            ->assertDontSee(self::NON_OFFERTO);
    }

    public function test_the_smartbox_page_hides_what_is_not_offered(): void
    {
        $this->withMixedAmenities(SmartboxPackage::factory()->create(['slug' => 'box-misto']));

        Livewire::test(SmartboxDetail::class, ['box' => 'box-misto'])
            ->assertOk()
            ->assertSee(self::OFFERTO)
            ->assertDontSee(self::NON_OFFERTO)
            ->assertDontSee(__('smartbox.animal_services'));
    }

    public function test_the_activity_page_hides_what_is_not_offered(): void
    {
        $this->withMixedAmenities(Event::factory()->activity(3)->create(['slug' => 'attivita-mista']));

        Livewire::test(ActivityDetail::class, ['activity' => 'attivita-mista'])
            ->assertOk()
            ->assertSee(self::OFFERTO)
            ->assertDontSee(self::NON_OFFERTO)
            ->assertSee(__('events.included'));
    }

    public function test_the_event_page_drops_the_included_section_when_nothing_is_offered(): void
    {
        $event = Event::factory()->create(['slug' => 'evento-spoglio']);
        $assente = Amenity::factory()->create(['name' => self::NON_OFFERTO, 'group' => Amenity::GROUP_ANIMAL]);
        $event->amenities()->sync([$assente->id => ['included' => false, 'position' => 1]]);

        Livewire::test(EventDetail::class, ['event' => 'evento-spoglio'])
            ->assertOk()
            ->assertDontSee(self::NON_OFFERTO)
            ->assertDontSee(__('events.included'));
    }
}
