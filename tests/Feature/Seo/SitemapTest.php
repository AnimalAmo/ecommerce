<?php

namespace Tests\Feature\Seo;

use App\Models\Article\Article;
use App\Models\Event\Event;
use App\Models\Page\Page;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleXMLElement;
use Tests\TestCase;

/**
 * /sitemap.xml è l'unico canale con cui diciamo a Google quali pagine esistono
 * e in quale lingua. Due cose lo rompono senza che nessuno se ne accorga: un
 * documento non valido (basta uno spazio prima della dichiarazione XML e i
 * crawler lo scartano) e un URL privato che ci finisce dentro.
 *
 * I test lavorano sui PERCORSI, mai sull'URL assoluto: l'host cambia fra questa
 * macchina, lo staging e animalamo.it, e un'asserzione sull'host sarebbe verde
 * solo qui. Le asserzioni sull'host mancante le fa già il RobotsTest.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    /** Namespace XHTML degli alternate hreflang (obbligatorio nella sitemap). */
    private const XHTML = 'http://www.w3.org/1999/xhtml';

    // ============ Giorno 1: catalogo vuoto ============

    /**
     * Il caso della messa online: nessun partner ha ancora pubblicato niente e
     * nemmeno il seed di piattaforma è girato. La sitemap non deve diventare un
     * documento vuoto o un 500 — le pagine statiche esistono comunque.
     */
    public function test_it_serves_valid_xml_with_the_static_pages_on_an_empty_catalogue(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        // locPaths() fa il parsing: se il documento non fosse XML valido si ferma qui.
        $paths = $this->locPaths($response->getContent());

        foreach ([
            '/', '/animal-holiday', '/eventi', '/smartbox', '/news', '/community',
            '/chi-siamo', '/contattaci', '/lavora-con-noi',
            '/termini-e-condizioni', '/termini-e-condizioni-fornitori', '/privacy-policy',
            '/en', '/en/animal-holiday', '/en/events', '/en/smartbox', '/en/news', '/en/community',
            '/en/about-us', '/en/contact-us', '/en/work-with-us',
            '/en/terms-and-conditions', '/en/supplier-terms-and-conditions', '/en/privacy-policy',
        ] as $path) {
            $this->assertContains($path, $paths, "La sitemap non elenca {$path}.");
        }
    }

    /** Un catalogo vuoto non è un errore: nessuna scheda prodotto, ma il documento resta valido. */
    public function test_an_empty_catalogue_produces_no_detail_urls(): void
    {
        $paths = $this->locPaths($this->get('/sitemap.xml')->getContent());

        foreach ($paths as $path) {
            $this->assertStringNotContainsString('/eventi/', $path);
            $this->assertStringNotContainsString('/smartbox/', $path);
            $this->assertStringNotContainsString('/news/', $path);
        }
    }

    // ============ Catalogo popolato ============

    /**
     * Ogni riga pubblicata deve avere il suo URL, in tutte e due le lingue: una
     * scheda che manca dalla sitemap esiste solo per chi ci arriva navigando.
     */
    public function test_it_lists_every_published_row_in_both_languages(): void
    {
        $this->seedCatalogue();

        $paths = $this->locPaths($this->get('/sitemap.xml')->getContent());

        foreach ([
            '/animal-holiday/toscana', '/en/animal-holiday/toscana',
            '/eventi/brunch-pet-friendly', '/en/events/brunch-pet-friendly',
            '/eventi/attivita/weekend-escursioni', '/en/events/activities/weekend-escursioni',
            '/smartbox/relax-lombardia', '/en/smartbox/relax-lombardia',
            '/news/vacanze-con-il-cane', '/en/news/vacanze-con-il-cane',
        ] as $path) {
            $this->assertContains($path, $paths, "La sitemap non elenca {$path}.");
        }
    }

    /**
     * L'alternate è la sola cosa che dice a Google che /eventi/x e
     * /en/events/x sono la stessa pagina in due lingue: senza, le due versioni
     * competono fra loro. Deve esserci su ENTRAMBE, non solo sull'italiana.
     */
    public function test_every_url_carries_the_alternates_of_all_the_locales(): void
    {
        $this->seedCatalogue();

        $body = $this->get('/sitemap.xml')->getContent();

        $expected = ['it' => '/eventi/brunch-pet-friendly', 'en' => '/en/events/brunch-pet-friendly'];

        foreach ($expected as $path) {
            $alternates = $this->alternatesFor($body, $path);

            $this->assertSame($expected['it'], $alternates['it'] ?? null);
            $this->assertSame($expected['en'], $alternates['en'] ?? null);
            // x-default: chi non parla né italiano né inglese atterra sull'italiano.
            $this->assertSame($expected['it'], $alternates['x-default'] ?? null);
        }
    }

    /**
     * La sitemap sta FUORI dal gruppo localizzato, quindi la lingua attiva
     * dipende da chi passa (una sessione, un cookie) e non dall'URL. Il
     * documento però è uno solo per tutti: se un domani gli URL li costruisse
     * route() invece del locale esplicito, un crawler con sessione inglese si
     * porterebbe a casa metà sitemap sbagliata, in silenzio.
     */
    public function test_the_document_does_not_depend_on_the_visitor_locale(): void
    {
        $this->seedCatalogue();

        $italian = $this->get('/sitemap.xml')->getContent();

        app()->setLocale('en');

        $this->assertSame($italian, $this->get('/sitemap.xml')->getContent());
    }

    /** Le pagine statiche sono tradotte anche loro: stesso trattamento delle schede. */
    public function test_the_static_pages_carry_their_alternates_too(): void
    {
        $alternates = $this->alternatesFor($this->get('/sitemap.xml')->getContent(), '/chi-siamo');

        $this->assertSame('/chi-siamo', $alternates['it'] ?? null);
        $this->assertSame('/en/about-us', $alternates['en'] ?? null);
    }

    /**
     * Un articolo con data futura non è pubblicato: /news/{slug} risponde 404
     * (NewsDetail usa lo scope published). Metterlo in sitemap significa
     * mandare Google su un 404 dichiarato da noi.
     */
    public function test_it_skips_the_articles_not_published_yet(): void
    {
        $this->article('vacanze-con-il-cane', now()->subDay());
        $this->article('articolo-di-domani', now()->addWeek());

        $paths = $this->locPaths($this->get('/sitemap.xml')->getContent());

        $this->assertContains('/news/vacanze-con-il-cane', $paths);
        $this->assertNotContains('/news/articolo-di-domani', $paths);
    }

    // ============ Quello che NON deve uscire ============

    /**
     * L'area partner ha già X-Robots-Tag: noindex (NoIndexPartnerPages su tutte
     * le route partner.*): elencarla in sitemap sarebbe dire una cosa e il suo
     * contrario. Carrello, checkout, profilo e reset password sono pagine di
     * sessione — contenuto diverso a ogni visita, o un token nell'URL.
     */
    public function test_it_never_lists_private_or_session_pages(): void
    {
        $this->seedCatalogue();

        $paths = $this->locPaths($this->get('/sitemap.xml')->getContent());

        foreach ([
            '/partner/dashboard', '/partner/i-miei-servizi', '/partner/crea-servizio',
            '/en/partner/dashboard', '/en/partner/my-services',
            '/iscrizione-partner', '/en/partner-registration',
            '/carrello', '/en/cart', '/checkout', '/en/checkout',
            '/profilo', '/profilo/i-miei-ordini', '/en/profile', '/en/profile/my-orders',
            '/preferiti', '/en/favourites',
        ] as $path) {
            $this->assertNotContains($path, $paths, "La sitemap elenca una pagina privata: {$path}.");
        }

        // Guardia larga: nessun percorso dell'area riservata, comunque si chiami.
        foreach ($paths as $path) {
            $this->assertStringNotContainsString('/partner', $path, "Percorso partner in sitemap: {$path}.");
            $this->assertStringNotContainsString('reimposta-password', $path);
            $this->assertStringNotContainsString('reset-password', $path);
        }
    }

    // ============ lastmod ============

    /**
     * lastmod ha senso solo dove esiste una data VERA di revisione: articoli
     * (published_at) e pagine legali (last_updated_at), entrambe dichiarate
     * dalla redazione. Sulle altre pagine inventarla — con now(), o con
     * updated_at che cambia a ogni riesecuzione del seeder — insegnerebbe a
     * Google che il nostro lastmod non vale niente.
     */
    public function test_it_dates_the_articles_and_the_legal_pages(): void
    {
        $this->article('vacanze-con-il-cane', now()->subMonth()->startOfDay());

        Page::create([
            'slug' => Page::PRIVACY,
            'title' => ['it' => 'Privacy', 'en' => 'Privacy'],
            'body' => ['it' => '<p>Testo</p>', 'en' => '<p>Text</p>'],
            'last_updated_at' => '2026-03-14',
        ]);

        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertSame(now()->subMonth()->format('Y-m-d'), $this->lastmodFor($body, '/news/vacanze-con-il-cane'));
        $this->assertSame(now()->subMonth()->format('Y-m-d'), $this->lastmodFor($body, '/en/news/vacanze-con-il-cane'));
        $this->assertSame('2026-03-14', $this->lastmodFor($body, '/privacy-policy'));
        $this->assertSame('2026-03-14', $this->lastmodFor($body, '/en/privacy-policy'));
    }

    /** Niente data inventata dove non ce n'è una: la home non ha un lastmod. */
    public function test_it_leaves_out_lastmod_where_there_is_no_real_date(): void
    {
        $this->assertNull($this->lastmodFor($this->get('/sitemap.xml')->getContent(), '/'));
    }

    /**
     * Le pagine legali restano in sitemap anche prima del PageSeeder: sono
     * rotte statiche, non contenuto opzionale. Senza riga a DB manca solo la
     * data, non l'URL.
     */
    public function test_a_legal_page_without_its_row_is_listed_without_lastmod(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertContains('/privacy-policy', $this->locPaths($body));
        $this->assertNull($this->lastmodFor($body, '/privacy-policy'));
    }

    // ============ Fixture ============

    private function seedCatalogue(): void
    {
        $region = Region::factory()->create(['name' => 'Toscana', 'slug' => 'toscana', 'position' => 1]);
        Structure::factory()->create(['region_id' => $region->id]);

        Event::factory()->create(['title' => 'Brunch pet friendly', 'slug' => 'brunch-pet-friendly']);
        Event::factory()->activity()->create(['title' => 'Weekend escursioni', 'slug' => 'weekend-escursioni']);
        SmartboxPackage::factory()->create(['title' => 'Relax Lombardia', 'slug' => 'relax-lombardia']);

        $this->article('vacanze-con-il-cane', now()->subDay());
    }

    private function article(string $slug, mixed $publishedAt): Article
    {
        return Article::create([
            'slug' => $slug,
            'title' => ['it' => 'Titolo', 'en' => 'Title'],
            'body' => ['it' => '<p>Testo</p>', 'en' => '<p>Text</p>'],
            'published_at' => $publishedAt,
        ]);
    }

    // ============ Lettura del documento ============

    /**
     * Percorsi di tutti i <loc>, host escluso.
     *
     * @return list<string>
     */
    private function locPaths(string $body): array
    {
        $paths = [];

        foreach ($this->xml($body)->url as $url) {
            $paths[] = $this->pathOf((string) $url->loc);
        }

        return $paths;
    }

    /**
     * Alternate del <url> con quel percorso: hreflang => percorso.
     *
     * @return array<string, string>
     */
    private function alternatesFor(string $body, string $path): array
    {
        foreach ($this->xml($body)->url as $url) {
            if ($this->pathOf((string) $url->loc) !== $path) {
                continue;
            }

            $alternates = [];

            foreach ($url->children(self::XHTML)->link as $link) {
                // $link['hreflang'] qui torna VUOTO: dopo children($ns) SimpleXML
                // cerca l'attributo nel namespace xhtml, mentre rel/hreflang/href
                // non hanno prefisso. attributes() rilegge quelli senza namespace.
                $attributes = $link->attributes();

                $alternates[(string) $attributes['hreflang']] = $this->pathOf((string) $attributes['href']);
            }

            return $alternates;
        }

        $this->fail("Nessun <url> per {$path}.");
    }

    private function lastmodFor(string $body, string $path): ?string
    {
        foreach ($this->xml($body)->url as $url) {
            if ($this->pathOf((string) $url->loc) !== $path) {
                continue;
            }

            return isset($url->lastmod) ? (string) $url->lastmod : null;
        }

        $this->fail("Nessun <url> per {$path}.");
    }

    private function xml(string $body): SimpleXMLElement
    {
        // Senza questo un documento malformato alzerebbe un warning PHP e il
        // fallimento parlerebbe di libxml, non della sitemap.
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body);

        $this->assertNotFalse($xml, 'La sitemap non è un documento XML valido: '.trim($body));

        return $xml;
    }

    /** L'host non è asseribile (cambia fra staging e produzione): confrontiamo i percorsi. */
    private function pathOf(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }
}
