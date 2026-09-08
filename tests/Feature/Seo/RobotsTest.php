<?php

namespace Tests\Feature\Seo;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * robots.txt è un file statico in public/: nginx lo serve prima che PHP entri
 * in gioco. Il kernel HTTP dei test non guarda in public/, quindi qui un
 * $this->get('/robots.txt') darebbe 404 anche col file al suo posto: non
 * proverebbe nulla sul 200 di produzione. Questi test verificano allora le due
 * condizioni che quel 200 lo producono davvero (file presente e leggibile,
 * nessuna rotta che rivendichi il path) più il contenuto, che è la parte che
 * si rompe davvero — uno slug tradotto che cambia e il path resta scoperto.
 */
class RobotsTest extends TestCase
{
    private string $robots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->robots = (string) file_get_contents(public_path('robots.txt'));
    }

    // ============ Il file arriva al crawler ============

    /**
     * Le due condizioni del 200: il file esiste in public/ e nessuna rotta
     * applicativa reclama /robots.txt (una rotta vincerebbe sul file statico e
     * servirebbe la sua risposta al posto suo). Se un giorno robots.txt passasse
     * dietro una rotta — per costruire la riga Sitemap da config('app.url')
     * invece che a mano — questo test va rosso apposta: va riscritto in
     * $this->get('/robots.txt')->assertOk(), che a quel punto è possibile.
     */
    public function test_robots_txt_is_a_readable_static_file_no_route_shadows(): void
    {
        $this->assertFileExists(public_path('robots.txt'));
        $this->assertFileIsReadable(public_path('robots.txt'));
        $this->assertNotSame('', trim($this->robots), 'robots.txt è vuoto: un crawler leggerebbe "tutto permesso".');

        $shadowing = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => in_array($uri, ['robots.txt', '/robots.txt'], true));

        $this->assertTrue($shadowing->isEmpty(), 'Una rotta applicativa copre /robots.txt: il file statico non verrebbe mai servito.');
    }

    // ============ Path privati, in ENTRAMBE le lingue ============

    /**
     * Il carrello è una pagina di sessione: contenuto diverso a ogni visita e
     * zero valore in SERP. Gli slug sono tradotti (lang/{it,en}/routes.php),
     * quindi vietarne uno solo lascia scoperta metà del sito.
     */
    public function test_it_disallows_the_cart_in_both_languages(): void
    {
        $this->assertDisallowed('/carrello');
        $this->assertDisallowed('/en/cart');
    }

    /** L'area personale mostra i dati dell'utente loggato: mai indicizzabile, in nessuna lingua. */
    public function test_it_disallows_the_profile_pages_in_both_languages(): void
    {
        $this->assertDisallowed('/profilo');
        $this->assertDisallowed('/profilo/i-miei-ordini');
        $this->assertDisallowed('/profilo/metodo-pagamento');
        $this->assertDisallowed('/en/profile');
        $this->assertDisallowed('/en/profile/my-orders');
        $this->assertDisallowed('/en/profile/payment-method');
    }

    /** Preferiti: lista per-utente, nessun contenuto stabile da indicizzare. */
    public function test_it_disallows_the_favourites_page_in_both_languages(): void
    {
        $this->assertDisallowed('/preferiti');
        $this->assertDisallowed('/en/favourites');
    }

    /** Il checkout apre PaymentIntent su Stripe: un crawler non ci deve nemmeno entrare. */
    public function test_it_disallows_the_checkout_in_both_languages(): void
    {
        $this->assertDisallowed('/checkout');
        $this->assertDisallowed('/en/checkout');
    }

    /**
     * Il token di reset password sta nell'URL ed è la sola credenziale della
     * pagina: non deve finire in nessun indice, né essere consumato da un fetch.
     */
    public function test_it_disallows_the_password_reset_links_in_both_languages(): void
    {
        $this->assertDisallowed('/reimposta-password/9f1c2b');
        $this->assertDisallowed('/en/reset-password/9f1c2b');
    }

    /**
     * Endpoint applicativi, non pagine: il webhook Stripe (POST /webhooks/stripe,
     * registrato da PaymentServiceProvider), il logout, il cambio lingua — un
     * redirect puro che genera path duplicati — e le chiamate interne di Livewire.
     */
    public function test_it_disallows_the_application_endpoints(): void
    {
        $this->assertDisallowed('/webhooks/stripe');
        $this->assertDisallowed('/logout');
        $this->assertDisallowed('/locale/en');
        $this->assertDisallowed('/livewire/update');
    }

    // ============ Vetrina pubblica: crawlabile ============

    /**
     * La home e Animal Times (/news, stesso slug nelle due lingue) sono il
     * contenuto che vogliamo indicizzato. Il test serve a intercettare una
     * regola scritta troppo larga: un `Disallow: /n` basta a far sparire
     * Animal Times senza che nessuno se ne accorga.
     */
    public function test_it_allows_the_home_and_animal_times_in_both_languages(): void
    {
        $this->assertCrawlable('/');
        $this->assertCrawlable('/news');
        $this->assertCrawlable('/news/vacanze-con-il-cane');
        $this->assertCrawlable('/en/news');
        $this->assertCrawlable('/en/news/holidays-with-your-dog');
    }

    /** Il resto della vetrina: catalogo, contenuti editoriali, pagine legali. */
    public function test_it_allows_the_public_storefront_in_both_languages(): void
    {
        foreach ([
            '/animal-holiday', '/animal-holiday/toscana', '/eventi', '/smartbox',
            '/community', '/chi-siamo', '/contattaci', '/lavora-con-noi',
            '/termini-e-condizioni', '/privacy-policy',
            '/en/animal-holiday', '/en/animal-holiday/tuscany', '/en/events', '/en/smartbox',
            '/en/community', '/en/about-us', '/en/contact-us', '/en/work-with-us',
            '/en/terms-and-conditions', '/en/privacy-policy',
        ] as $path) {
            $this->assertCrawlable($path);
        }
    }

    // ============ Area partner: la scelta deliberata ============

    /**
     * Scelta deliberata, non una dimenticanza. NoIndexPartnerPages manda
     * X-Robots-Tag: noindex, nofollow su tutte le route partner.*, e un header
     * si legge SOLO se il crawler può scaricare la pagina. Le due pagine
     * pubbliche del funnel (iscrizione partner, step 2) restano quindi
     * crawlabili apposta: vietarle qui impedirebbe a Google di leggere il
     * noindex, che è l'unica cosa che le toglie davvero dall'indice.
     */
    public function test_it_lets_crawlers_reach_the_public_partner_funnel_so_the_noindex_is_read(): void
    {
        $this->assertCrawlable('/iscrizione-partner');
        $this->assertCrawlable('/iscrizione-partner/servizi');
        $this->assertCrawlable('/en/partner-registration');
        $this->assertCrawlable('/en/partner-registration/services');
    }

    /**
     * L'area riservata invece resta vietata: è dietro auth+partner, da ospite
     * risponde solo con un redirect alla home. Non c'è nessun noindex da far
     * leggere — su un 302 l'header non viene nemmeno guardato — quindi il
     * Disallow qui è puro risparmio di crawl budget, senza controindicazioni.
     */
    public function test_it_disallows_the_reserved_partner_area_in_both_languages(): void
    {
        $this->assertDisallowed('/partner/dashboard');
        $this->assertDisallowed('/partner/i-miei-servizi');
        $this->assertDisallowed('/partner/struttura/hotel/titolo');
        $this->assertDisallowed('/en/partner/dashboard');
        $this->assertDisallowed('/en/partner/my-services');
        $this->assertDisallowed('/en/partner/structure/hotel/title');
    }

    // ============ Sitemap ============

    /**
     * Lo standard vuole un URL ASSOLUTO: un `Sitemap: /sitemap.xml` viene
     * ignorato. Essendo robots.txt un file statico, quell'host è scritto a mano
     * e non può venire da config('app.url'): questo test è la sveglia — se il
     * dominio cambia, robots.txt non si aggiorna da solo e il rosso qui lo dice.
     */
    public function test_it_names_the_sitemap_with_an_absolute_production_url(): void
    {
        $this->assertMatchesRegularExpression(
            '/^Sitemap: https:\/\/animalamo\.it\/sitemap\.xml$/m',
            $this->robots,
            'Manca (o non è assoluta) la riga Sitemap: i crawler non troverebbero /sitemap.xml.'
        );
    }

    // ============ Matcher robots.txt ============

    private function assertDisallowed(string $path): void
    {
        $this->assertTrue($this->isDisallowed($path), "robots.txt lascia crawlabile un path privato: {$path}");
    }

    private function assertCrawlable(string $path): void
    {
        $this->assertFalse($this->isDisallowed($path), "robots.txt blocca un path pubblico: {$path}");
    }

    /**
     * Regola di Google: fra tutte le direttive che combaciano vince la più
     * lunga, a parità di lunghezza vince Allow. Riprodurla qui (invece di
     * cercare la stringa "Disallow: /carrello" nel file) è ciò che rende il
     * test capace di bocciare una regola scritta male: `Disallow: /` passerebbe
     * qualsiasi assertSee e blocca l'intero sito.
     */
    private function isDisallowed(string $path): bool
    {
        $winner = null;

        foreach ($this->rulesForEveryCrawler() as [$field, $pattern]) {
            // "Disallow:" senza valore non blocca nulla: è la forma canonica di "tutto permesso".
            if ($pattern === '' || ! $this->pathMatches($pattern, $path)) {
                continue;
            }

            $length = strlen($pattern);

            if ($winner === null || $length > $winner['length'] || ($length === $winner['length'] && $field === 'allow')) {
                $winner = ['allow' => $field === 'allow', 'length' => $length];
            }
        }

        return $winner !== null && ! $winner['allow'];
    }

    /** Prefisso, con `*` jolly e `$` di fine path come da specifica. */
    private function pathMatches(string $pattern, string $path): bool
    {
        $anchored = str_ends_with($pattern, '$');
        $regex = str_replace('\*', '.*', preg_quote(rtrim($pattern, '$'), '#'));

        return (bool) preg_match('#^'.$regex.($anchored ? '$' : '').'#', $path);
    }

    /**
     * Le sole direttive del gruppo `User-agent: *`. Un gruppo per un bot
     * specifico non deve inquinare il verdetto sul crawler generico.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function rulesForEveryCrawler(): array
    {
        $rules = [];
        $insideGroup = false;

        foreach (preg_split('/\R/', $this->robots) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                $insideGroup = $value === '*';

                continue;
            }

            if ($insideGroup && in_array($field, ['allow', 'disallow'], true)) {
                $rules[] = [$field, $value];
            }
        }

        return $rules;
    }
}
