<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * «Vedere tutte le foto» sulle cinque schede di dettaglio (segnalazione di
 * Matteo, 29/09/2026, su /eventi/attivita/bauclub-27): il pulsante non apriva
 * niente — era un flux:button senza azione, con un TODO — e compariva anche
 * sulle schede che di foto ne hanno una sola.
 *
 * Ora compare solo con almeno due foto e apre la galleria. Le foto sono la
 * copertina più la galleria che il publisher fotografa dalla bozza: le schede
 * del catalogo demo hanno la sola copertina e il pulsante non lo mostrano.
 */
class PhotoGalleryTest extends TestCase
{
    use RefreshDatabase;

    /** Le quattro foto minime del wizard, nell'ordine della bozza: la prima è la copertina. */
    private const PHOTOS = [
        'structure-photos/uno.jpg',
        'structure-photos/due.jpg',
        'structure-photos/tre.jpg',
        'structure-photos/quattro.jpg',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app()->setLocale('it');
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    private static function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    public function test_una_scheda_del_catalogo_demo_ha_la_sola_copertina(): void
    {
        $activity = Event::factory()->activity()->create();

        $this->assertSame([asset('img/xd/activity-detail-hero.jpg')], $activity->galleryImageUrls());
    }

    public function test_la_galleria_parte_dalla_copertina_senza_ripeterla(): void
    {
        $activity = Event::factory()->activity()->create([
            'img' => self::PHOTOS[0],
            'hero_img' => self::PHOTOS[0],
            'gallery' => self::PHOTOS,
        ]);

        $this->assertSame(array_map(self::url(...), self::PHOTOS), $activity->galleryImageUrls());
    }

    /**
     * La prima foto della galleria è quella che il cliente ha già davanti
     * nell'hero, anche quando le due colonne non coincidono.
     */
    public function test_una_copertina_fuori_dalla_galleria_resta_in_testa(): void
    {
        $activity = Event::factory()->activity()->create([
            'hero_img' => 'structure-photos/copertina.jpg',
            'gallery' => ['structure-photos/due.jpg', 'structure-photos/tre.jpg'],
        ]);

        $this->assertSame([
            self::url('structure-photos/copertina.jpg'),
            self::url('structure-photos/due.jpg'),
            self::url('structure-photos/tre.jpg'),
        ], $activity->galleryImageUrls());
    }

    /** Colonne img NOT NULL: una bozza senza foto pubblica '' e l'hero degrada a niente. */
    public function test_senza_copertina_ne_galleria_non_ci_sono_foto(): void
    {
        $activity = Event::factory()->activity()->create(['img' => '', 'hero_img' => '', 'gallery' => null]);

        $this->assertSame([], $activity->galleryImageUrls());
    }

    /**
     * Le cinque schede col pulsante, ognuna col suo file di traduzioni. La
     * closure riceve le colonne immagine e rende la pagina.
     *
     * @return array<string, array{Closure(array): Testable, string}>
     */
    public static function pages(): array
    {
        return [
            'attività' => [
                function (array $images): Testable {
                    Event::factory()->activity()->create(['slug' => 'bauclub', ...$images]);

                    return Livewire::test(ActivityDetail::class, ['activity' => 'bauclub']);
                },
                'events.view_all_photos',
            ],
            'evento' => [
                function (array $images): Testable {
                    Event::factory()->create(['slug' => 'festa', ...$images]);

                    return Livewire::test(EventDetail::class, ['event' => 'festa']);
                },
                'events.view_all_photos',
            ],
            'smartbox' => [
                function (array $images): Testable {
                    SmartboxPackage::factory()->create(['slug' => 'relax', ...$images]);

                    return Livewire::test(SmartboxDetail::class, ['box' => 'relax']);
                },
                'smartbox.view_all_photos',
            ],
            'struttura' => [
                function (array $images): Testable {
                    Structure::factory()->create(['slug' => 'hotel', ...$images]);

                    return Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel']);
                },
                'holiday.view_all_photos',
            ],
            'servizio' => [
                function (array $images): Testable {
                    Structure::factory()->service()->create(['slug' => 'dog-sitting', ...$images]);

                    return Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting']);
                },
                'holiday.view_all_photos',
            ],
        ];
    }

    /** @param  Closure(array): Testable  $render */
    #[DataProvider('pages')]
    public function test_con_piu_foto_il_pulsante_apre_la_galleria(Closure $render, string $label): void
    {
        $page = $render([
            'img' => self::PHOTOS[0],
            'hero_img' => self::PHOTOS[0],
            'gallery' => self::PHOTOS,
        ]);

        $page->assertOk()
            ->assertSee(__($label))
            // Il pulsante è collegato: il trigger apre il modale che sta nella pagina.
            ->assertSeeHtml("name: 'photo-gallery'")
            ->assertSeeHtml('data-modal="photo-gallery"');

        foreach (self::PHOTOS as $path) {
            $page->assertSeeHtml(self::url($path));
        }

        // Una slide per foto: la copertina non si ripete in coda.
        $this->assertSame(count(self::PHOTOS), substr_count($page->html(), 'data-flux-carousel-slide'));
    }

    /** @param  Closure(array): Testable  $render */
    #[DataProvider('pages')]
    public function test_con_una_foto_sola_il_pulsante_non_compare(Closure $render, string $label): void
    {
        // La scheda del catalogo demo: copertina dal template, nessuna galleria.
        $render(['gallery' => null])
            ->assertOk()
            ->assertDontSee(__($label))
            ->assertDontSeeHtml('data-modal="photo-gallery"');
    }

    /** Una galleria di una sola foto, uguale alla copertina, resta una foto sola. */
    #[DataProvider('pages')]
    public function test_una_galleria_con_la_sola_copertina_non_mostra_il_pulsante(Closure $render, string $label): void
    {
        $render([
            'img' => self::PHOTOS[0],
            'hero_img' => self::PHOTOS[0],
            'gallery' => [self::PHOTOS[0]],
        ])
            ->assertOk()
            ->assertDontSee(__($label))
            ->assertDontSeeHtml('data-modal="photo-gallery"');
    }
}
