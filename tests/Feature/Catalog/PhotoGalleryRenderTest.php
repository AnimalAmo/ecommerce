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
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * «Vedere tutte le foto», i casi che PhotoGalleryTest non tocca: la pagina
 * inglese, le stringhe di Flux tradotte, un nome con caratteri da escapare,
 * il pulsante che sopravvive a un re-render Livewire, e la pagina vera
 * (layout compreso) sull'URL della segnalazione.
 */
class PhotoGalleryRenderTest extends TestCase
{
    use RefreshDatabase;

    private const PHOTOS = [
        'structure-photos/uno.jpg',
        'structure-photos/due.jpg',
        'structure-photos/tre.jpg',
        'structure-photos/quattro.jpg',
    ];

    /** Apostrofo e & insieme: devono uscire escapati una volta sola. */
    private const TITLE = "L'uno & l'altro";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app()->setLocale('it');
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    private static function images(): array
    {
        return [
            'img' => self::PHOTOS[0],
            'hero_img' => self::PHOTOS[0],
            'gallery' => self::PHOTOS,
        ];
    }

    /**
     * Le cinque schede: chi le rende, la chiave del pulsante, e un'azione
     * della pagina che la fa ridisegnare (stepper, tendine, pop-up).
     *
     * @return array<string, array{Closure(): Testable, string, Closure(Testable): Testable}>
     */
    public static function pages(): array
    {
        $title = ['it' => self::TITLE, 'en' => self::TITLE];

        return [
            'attività' => [
                function () use ($title): Testable {
                    Event::factory()->activity()->create(['slug' => 'bauclub', 'title' => $title, ...self::images()]);

                    return Livewire::test(ActivityDetail::class, ['activity' => 'bauclub']);
                },
                'events.view_all_photos',
                fn (Testable $page): Testable => $page->call('toggleField', 'ospiti'),
            ],
            'evento' => [
                function () use ($title): Testable {
                    Event::factory()->create(['slug' => 'festa', 'title' => $title, ...self::images()]);

                    return Livewire::test(EventDetail::class, ['event' => 'festa']);
                },
                'events.view_all_photos',
                fn (Testable $page): Testable => $page->call('closeCartPopup'),
            ],
            'smartbox' => [
                function () use ($title): Testable {
                    SmartboxPackage::factory()->create(['slug' => 'relax', 'title' => $title, ...self::images()]);

                    return Livewire::test(SmartboxDetail::class, ['box' => 'relax']);
                },
                'smartbox.view_all_photos',
                fn (Testable $page): Testable => $page->call('toggleAnimals'),
            ],
            'struttura' => [
                function () use ($title): Testable {
                    Structure::factory()->create(['slug' => 'hotel', 'name' => $title, ...self::images()]);

                    return Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel']);
                },
                'holiday.view_all_photos',
                fn (Testable $page): Testable => $page->call('toggleField', 'ospiti'),
            ],
            'servizio' => [
                function () use ($title): Testable {
                    Structure::factory()->service()->create(['slug' => 'dog-sitting', 'name' => $title, ...self::images()]);

                    return Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting']);
                },
                'holiday.view_all_photos',
                fn (Testable $page): Testable => $page->call('toggleField', 'orari'),
            ],
        ];
    }

    private function assertGalleryIsWired(Testable $page, string $label): void
    {
        $page->assertOk()
            ->assertSee(__($label))
            ->assertSeeHtml("name: 'photo-gallery'")
            ->assertSeeHtml('data-modal="photo-gallery"');

        $this->assertSame(count(self::PHOTOS), substr_count($page->html(), 'data-flux-carousel-slide'));
    }

    /**
     * Le pagine si ridisegnano a ogni clic su stepper, tendine e pop-up: il
     * pulsante e il modale devono esserci ancora, con tutte le foto.
     *
     * @param  Closure(): Testable  $render
     * @param  Closure(Testable): Testable  $rerender
     */
    #[DataProvider('pages')]
    public function test_il_pulsante_e_la_galleria_sopravvivono_a_un_re_render(Closure $render, string $label, Closure $rerender): void
    {
        $page = $render();
        $this->assertGalleryIsWired($page, $label);

        $page = $rerender($page);
        $this->assertGalleryIsWired($page, $label);
    }

    /**
     * Sulla pagina inglese pulsante, frecce, titolo del carousel e testi
     * alternativi escono in inglese: le chiavi `catalog.gallery.*` ci sono
     * in lang/en, non solo in lang/it.
     *
     * @param  Closure(): Testable  $render
     */
    #[DataProvider('pages')]
    public function test_la_galleria_sulla_pagina_inglese_parla_inglese(Closure $render, string $label, Closure $rerender): void
    {
        app()->setLocale('en');

        $page = $render();

        $page->assertOk()
            ->assertSee('See all photos')
            ->assertSeeHtml('aria-label="Previous photo"')
            ->assertSeeHtml('aria-label="Next photo"')
            ->assertSeeHtml('aria-label="Photos of L&#039;uno &amp; l&#039;altro"')
            ->assertSeeHtml('alt="L&#039;uno &amp; l&#039;altro, photo 1 of 4"')
            ->assertSeeHtml('alt="L&#039;uno &amp; l&#039;altro, photo 4 of 4"')
            // Nessuna chiave grezza e nessun residuo italiano.
            ->assertDontSee('catalog.gallery.')
            ->assertDontSee('Vedere tutte le foto')
            ->assertDontSeeHtml('Foto precedente')
            ->assertDontSeeHtml('Foto successiva');
    }

    /**
     * Sulla pagina italiana anche le stringhe che disegna Flux (indicatori,
     * chiusura del modale) sono tradotte, e le frecce predefinite del carousel
     * («Previous slide»/«Next slide») sono sostituite dalle nostre.
     *
     * @param  Closure(): Testable  $render
     */
    #[DataProvider('pages')]
    public function test_la_galleria_sulla_pagina_italiana_non_ha_stringhe_inglesi_di_flux(Closure $render, string $label, Closure $rerender): void
    {
        $page = $render();

        $page->assertOk()
            ->assertSeeHtml('aria-label="Foto precedente"')
            ->assertSeeHtml('aria-label="Foto successiva"')
            ->assertSeeHtml('aria-label="Scegli cosa mostrare"')
            ->assertSeeHtml('aria-label="Foto di L&#039;uno &amp; l&#039;altro"')
            ->assertSeeHtml('alt="L&#039;uno &amp; l&#039;altro, foto 2 di 4"')
            ->assertDontSee('catalog.gallery.')
            ->assertDontSeeHtml('Choose slide to display')
            ->assertDontSeeHtml('Previous slide')
            ->assertDontSeeHtml('Next slide')
            ->assertDontSeeHtml('aria-label="Items"')
            ->assertDontSeeHtml('Close modal');
    }

    /**
     * Il nome della scheda passa per titolo, aria-label e alt: un escape
     * doppio (`&amp;amp;`) comparirebbe al cliente come testo.
     *
     * @param  Closure(): Testable  $render
     */
    #[DataProvider('pages')]
    public function test_il_nome_della_scheda_non_e_escapato_due_volte(Closure $render, string $label, Closure $rerender): void
    {
        $html = $render()->html();

        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;#039;', $html);
    }

    /**
     * La pagina della segnalazione (/eventi/attivita/<slug>) resa per intero,
     * layout compreso, non solo il componente: il pulsante c'è ed è collegato
     * al modale che la stessa risposta contiene.
     */
    public function test_la_pagina_attivita_della_segnalazione_apre_la_galleria(): void
    {
        Event::factory()->activity()->create(['slug' => 'bauclub-27', 'title' => 'Bauclub', ...self::images()]);

        $response = $this->get('/eventi/attivita/bauclub-27')
            ->assertOk()
            ->assertSee('Vedere tutte le foto')
            ->assertSee("name: 'photo-gallery'", false)
            ->assertSee('data-modal="photo-gallery"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'data-modal="photo-gallery"'), 'Un modale solo per pagina.');
        $this->assertSame(count(self::PHOTOS), substr_count($response->getContent(), 'data-flux-carousel-slide'));
    }

    /** La stessa pagina in inglese, dall'URL localizzato (/en/events/activities/<slug>). */
    public function test_la_pagina_attivita_inglese_apre_la_galleria(): void
    {
        Event::factory()->activity()->create(['slug' => 'bauclub-27', 'title' => ['it' => 'Bauclub', 'en' => 'Bauclub EN'], ...self::images()]);

        $this->reloadRoutesFor('/en/events/activities/bauclub-27');

        $this->get('/en/events/activities/bauclub-27')
            ->assertOk()
            ->assertSee('See all photos')
            ->assertSee('data-modal="photo-gallery"', false)
            ->assertSee('alt="Bauclub EN, photo 1 of 4"', false)
            ->assertDontSee('Vedere tutte le foto');
    }

    /** La pagina demo (sola copertina del template) non mostra né pulsante né modale, anche resa per intero. */
    public function test_la_pagina_attivita_demo_non_ha_la_galleria(): void
    {
        Event::factory()->activity()->create(['slug' => 'demo']);

        $this->get('/eventi/attivita/demo')
            ->assertOk()
            ->assertDontSee('Vedere tutte le foto')
            ->assertDontSee('data-modal="photo-gallery"', false)
            ->assertDontSee('data-flux-carousel', false);
    }

    /**
     * Stesso helper di LocalizationTest: il test harness registra solo gli
     * slug italiani, perché le rotte si caricano prima che esista una request.
     */
    private function reloadRoutesFor(string $uri): void
    {
        $this->app->instance('request', Request::create($uri, 'GET'));

        $this->app->forgetInstance(\Mcamara\LaravelLocalization\LaravelLocalization::class);
        $this->app->forgetInstance('laravellocalization');
        LaravelLocalization::clearResolvedInstance('laravellocalization');
        $loc = app('laravellocalization');
        $loc->getSupportedLocales();
        $loc->setLocale();

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }
}
