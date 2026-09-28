<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Structure\Structure;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Testing\TestResponse;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Mappa Google sotto consenso Iubenda (finalità 3): il server non emette mai
 * un src Google, l'iframe è taggato a mano (about:blank + data-suppressedsrc)
 * dentro un wrapper wire:ignore, sotto c'è il segnaposto e la pill del
 * chiamante è sorella successiva del wrapper, come vogliono le regole di app.css.
 *
 * Le verifiche lavorano sul DOM e non su assertSee: il footer ha già un
 * "Gestisci cookie" e data-suppressedsrc="https://…/embed" contiene la
 * sottostringa src="https://…/embed", quindi un assertSee di pagina
 * passerebbe o fallirebbe per il motivo sbagliato.
 */
class GoogleMapConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Le cinque pagine che montano x-google-map, con la classe d'altezza che il
     * chiamante passa al componente: deve finire sul wrapper, perché i figli sono
     * tutti absolute e senza altezza il riquadro collasserebbe a zero.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pageProvider(): array
    {
        return [
            'structure detail' => ['structure', 'h-[389px]'],
            'service detail' => ['service', 'h-[389px]'],
            'event detail' => ['event', 'h-[576px]'],
            'activity detail' => ['activity', 'h-full'],
            'contact page' => ['contact', 'h-full'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_the_map_waits_for_consent_behind_a_placeholder(string $page, string $heightClass): void
    {
        config(['services.google.maps_key' => 'test-key']);

        [$url, $query] = $this->pageFor($page);

        $response = $this->get($url)->assertOk();

        $this->assertNoLiveGoogleSrc($response);

        [$xpath, $map] = $this->mapWrapper($response);

        // Wrapper: wire:ignore (il morph non riscrive il src), relative + altezza del chiamante.
        $this->assertTrue($map->hasAttribute('wire:ignore'), 'Il wrapper della mappa non ha wire:ignore.');
        $this->assertContains('relative', $this->classes($map));
        $this->assertContains($heightClass, $this->classes($map));

        // Iframe taggato a mano per Iubenda, figlio diretto del wrapper (lo vuole [data-map] > iframe).
        $iframe = $this->single($xpath, './iframe', $map, 'iframe figlio diretto di [data-map]');
        $this->assertSame('about:blank', $iframe->getAttribute('src'));
        $this->assertContains('_iub_cs_activate', $this->classes($iframe));
        $this->assertSame('3', $iframe->getAttribute('data-iub-purposes'));
        $this->assertNotSame('', trim($iframe->getAttribute('title')), "L'iframe non ha un nome accessibile.");
        // Iubenda ricostruisce l'iframe al consenso copiando solo gli attributi non vuoti.
        $this->assertSame('allowfullscreen', $iframe->getAttribute('allowfullscreen'));

        $suppressed = $iframe->getAttribute('data-suppressedsrc');
        $this->assertStringStartsWith('https://www.google.com/maps/embed/v1/place?', $suppressed);
        $this->assertStringContainsString('key=test-key', $suppressed);
        $this->assertStringContainsString(urlencode($query), $suppressed);
        parse_str((string) parse_url($suppressed, PHP_URL_QUERY), $embed);
        $this->assertSame('test-key', $embed['key'] ?? null);
        $this->assertSame($query, $embed['q'] ?? null);

        // Segnaposto sotto l'iframe: deve precederlo, altrimenti (absolute, stesso z)
        // coprirebbe la mappa anche dopo il consenso.
        $placeholder = $this->single($xpath, './*[@data-map-placeholder]', $map, 'segnaposto');
        $this->assertSame(1.0, $xpath->evaluate('count(preceding-sibling::*[@data-map-placeholder])', $iframe));
        $this->assertStringContainsString(
            'La mappa di Google compare solo se accetti i suoi cookie.',
            $this->text($placeholder),
        );

        // "Apri in Google Maps": navigazione semplice, nessun consenso richiesto.
        $search = $this->single($xpath, './/a[starts-with(@href, "https://www.google.com/maps/search/")]', $placeholder, 'link Google Maps');
        parse_str((string) parse_url($search->getAttribute('href'), PHP_URL_QUERY), $searchParams);
        $this->assertSame('1', $searchParams['api'] ?? null);
        $this->assertSame($query, $searchParams['query'] ?? null);
        $this->assertSame('_blank', $search->getAttribute('target'));
        $this->assertContains('noopener', preg_split('/\s+/', $search->getAttribute('rel')));
        $this->assertSame('Apri in Google Maps', $this->text($search));

        // Preferenze Iubenda: solo se la policy offre la finalità 3.
        $prefs = $this->single($xpath, './/a[contains(concat(" ", normalize-space(@class), " "), " iubenda-cs-preferences-link ")]', $placeholder, 'link preferenze Iubenda');
        $this->assertSame('#', $prefs->getAttribute('href'));
        $this->assertTrue($prefs->hasAttribute('x-cloak'), 'Il link preferenze non ha x-cloak: lampeggerebbe prima di Alpine.');
        // csPurposes è un array di numeri: .map(String) rende il confronto indifferente al tipo.
        $this->assertSame("(window._iub?.csPurposes ?? []).map(String).includes('3')", $prefs->getAttribute('x-show'));
        $this->assertSame('Gestisci cookie', $this->text($prefs));

        // Pill del chiamante: sorella successiva del wrapper (lo vuole [data-map]:has(...) ~ [data-map-pill]).
        $this->single($xpath, './following-sibling::*[@data-map-pill]', $map, 'pill [data-map-pill] sorella successiva del wrapper');
    }

    public function test_app_css_hides_the_blocked_map_and_its_pill_until_consent(): void
    {
        // Le regole vivono in app.css e i test PHP non eseguono CSS: qui si fissa almeno il contratto
        // dei selettori con il markup del componente, così un rename da una parte sola fallisce.
        $css = (string) preg_replace('/\s+/', ' ', (string) file_get_contents(resource_path('css/app.css')));

        $this->assertStringContainsString('[data-map] > iframe[src="about:blank"] { visibility: hidden; }', $css);
        $this->assertStringContainsString('[data-map]:has(> iframe[src="about:blank"]) ~ [data-map-pill] { display: none; }', $css);
        $this->assertStringContainsString('[data-map]:has(> iframe:not([src="about:blank"])) > [data-map-placeholder] { visibility: hidden; }', $css);
    }

    public function test_without_a_key_the_structure_keeps_the_static_screenshot(): void
    {
        $response = $this->get('/animal-holiday/lombardia/hotel-brescia')->assertOk();

        $xpath = $this->xpath($response);

        $this->assertSame(0, $xpath->query('//*[@data-map]')->length, 'Senza chiave non deve esserci il wrapper [data-map].');
        $this->assertSame(0, $xpath->query('//iframe[@data-suppressedsrc]')->length);
        $this->assertSame(1, $xpath->query('//img[contains(@src, "struttura-mappa")]')->length);
        $this->assertNoLiveGoogleSrc($response);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function englishPageProvider(): array
    {
        return [
            'contact page' => ['/en/contact-us'],
            'structure detail' => ['/en/animal-holiday/lombardia/hotel-brescia'],
        ];
    }

    #[DataProvider('englishPageProvider')]
    public function test_the_placeholder_speaks_english_on_english_pages(string $url): void
    {
        config(['services.google.maps_key' => 'test-key']);

        $this->reloadRoutesFor($url);

        $response = $this->get($url)->assertOk();

        $this->assertNoLiveGoogleSrc($response);

        [$xpath, $map] = $this->mapWrapper($response);
        $placeholder = $this->single($xpath, './*[@data-map-placeholder]', $map, 'segnaposto');

        $this->assertStringContainsString('The Google map only appears once you accept its cookies.', $this->text($placeholder));
        $this->assertStringNotContainsString('La mappa di Google', $this->text($placeholder));
        $this->assertSame(
            'Open in Google Maps',
            $this->text($this->single($xpath, './/a[starts-with(@href, "https://www.google.com/maps/search/")]', $placeholder, 'link Google Maps')),
        );
        $this->assertSame(
            'Manage cookies',
            $this->text($this->single($xpath, './/a[contains(@class, "iubenda-cs-preferences-link")]', $placeholder, 'link preferenze Iubenda')),
        );
    }

    /**
     * URL della pagina e query "place" che la sua mappa deve cercare.
     *
     * @return array{0: string, 1: string}
     */
    private function pageFor(string $page): array
    {
        return match ($page) {
            'structure' => ['/animal-holiday/lombardia/hotel-brescia', 'Hotel Brescia, Dario Boario Terme (BS), Italia'],
            'service' => $this->servicePage(),
            'event' => $this->eventPage(ProductType::Event),
            'activity' => $this->eventPage(ProductType::Activity),
            'contact' => ['/contattaci', 'Via Cattani 11, Contà (Trento), Italia'],
        };
    }

    /** @return array{0: string, 1: string} */
    private function servicePage(): array
    {
        $service = Structure::where('slug', 'dog-sitting')
            ->where('type', ProductType::Service)
            ->orderBy('position')
            ->firstOrFail();

        $this->assertNotNull($service->mapQuery());

        return ['/animal-holiday/lombardia/servizi/dog-sitting', $service->mapQuery()];
    }

    /** @return array{0: string, 1: string} */
    private function eventPage(ProductType $type): array
    {
        $event = Event::query()
            ->where('type', $type)
            ->whereHas('venue', fn ($venue) => $venue->whereNotNull('address')->where('address', '!=', ''))
            ->firstOrFail();

        $this->assertNotNull($event->venue->mapQuery());

        $prefix = $type === ProductType::Activity ? '/eventi/attivita/' : '/eventi/';

        return [$prefix.$event->slug, $event->venue->mapQuery()];
    }

    /**
     * Nessun iframe col src di Google nella risposta del server: né nel DOM né
     * come attributo src nel sorgente (data-suppressedsrc resta ammesso).
     */
    private function assertNoLiveGoogleSrc(TestResponse $response): void
    {
        // preg_match e non assertDoesNotMatchRegularExpression: quest'ultima stamperebbe l'intera pagina.
        $this->assertSame(
            0,
            preg_match('/[\s"\'<]src\s*=\s*["\']?https:\/\/www\.google\.com\/maps\/embed/i', (string) $response->getContent()),
            'Il server emette un src Google Maps live: la mappa si caricherebbe senza consenso.',
        );
        $this->assertSame(0, $this->xpath($response)->query('//iframe[contains(@src, "google.com/maps")]')->length);
    }

    /** @return array{0: DOMXPath, 1: DOMElement} */
    private function mapWrapper(TestResponse $response): array
    {
        $xpath = $this->xpath($response);

        return [$xpath, $this->single($xpath, '//*[@data-map]', null, 'wrapper [data-map]')];
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // Il prologo forza l'UTF-8: il parser HTML di libxml partirebbe da ISO-8859-1.
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    private function single(DOMXPath $xpath, string $expression, ?DOMElement $context, string $what): DOMElement
    {
        $nodes = $context === null ? $xpath->query($expression) : $xpath->query($expression, $context);

        $this->assertSame(1, $nodes->length, "Atteso esattamente un elemento: {$what} ({$expression}).");
        $this->assertInstanceOf(DOMElement::class, $nodes->item(0));

        return $nodes->item(0);
    }

    /** @return list<string> */
    private function classes(DOMElement $element): array
    {
        return preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function text(DOMElement $element): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $element->textContent));
    }

    /**
     * Ricarica le rotte con la richiesta /en già legata, così mcamara registra gli
     * slug inglesi (spiegazione completa in LocalizationTest::reloadRoutesFor).
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
