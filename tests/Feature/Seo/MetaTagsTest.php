<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Lang;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

/**
 * Il <head> del layout unico: description, Open Graph, Twitter card,
 * canonical e hreflang. Sono i soli segnali che il sito dà a Google e ai
 * social; finché non esistono, ogni condivisione mostra una card vuota e
 * Google non sa quale delle due lingue indicizzare — per questo stanno
 * sotto test e non nella sola review visiva.
 */
class MetaTagsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ricarica le rotte con la request di destinazione già legata al
     * container, così mcamara registra gli slug tradotti di quella lingua.
     *
     * (Stesso helper di Tests\Feature\LocalizationTest: in produzione i file
     * di rotta si caricano DOPO la request, in test la app parte in setUp()
     * quando nessuna URL esiste ancora e resterebbero i soli slug italiani.)
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

    /** Il valore dell'attributo content del primo meta con quel name/property. */
    private function metaContent(string $html, string $attribute, string $name): ?string
    {
        $pattern = '/<meta\s+'.$attribute.'="'.preg_quote($name, '/').'"\s+content="([^"]*)"/';

        return preg_match($pattern, $html, $m) === 1 ? html_entity_decode($m[1], ENT_QUOTES, 'UTF-8') : null;
    }

    /** L'href del primo <link> con quel rel (e, se dato, quell'hreflang). */
    private function linkHref(string $html, string $rel, ?string $hreflang = null): ?string
    {
        $pattern = $hreflang === null
            ? '/<link\s+rel="'.preg_quote($rel, '/').'"\s+href="([^"]*)"/'
            : '/<link\s+rel="'.preg_quote($rel, '/').'"\s+hreflang="'.preg_quote($hreflang, '/').'"\s+href="([^"]*)"/';

        return preg_match($pattern, $html, $m) === 1 ? html_entity_decode($m[1], ENT_QUOTES, 'UTF-8') : null;
    }

    // ============ Meta description ============

    public function test_home_carries_its_own_meta_description(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(__('seo.home'), $this->metaContent($html, 'name', 'description'));
    }

    public function test_every_description_is_between_150_and_160_characters(): void
    {
        // Sotto i 150 Google riempie lo snippet con testo pescato dalla pagina,
        // sopra i 160 lo tronca a metà frase: entrambi i casi buttano la copy.
        foreach (['it', 'en'] as $locale) {
            foreach (Lang::get('seo', [], $locale) as $key => $description) {
                $length = mb_strlen($description);

                $this->assertGreaterThanOrEqual(150, $length, "lang/{$locale}/seo.php: '{$key}' è di soli {$length} caratteri");
                $this->assertLessThanOrEqual(160, $length, "lang/{$locale}/seo.php: '{$key}' è di {$length} caratteri");
            }
        }
    }

    public function test_a_page_without_its_own_copy_falls_back_to_the_site_description(): void
    {
        // Il carrello è una pagina transazionale: non avrà mai copy SEO propria.
        // Se un giorno l'avesse, questo test va spostato su un'altra rotta senza
        // voce in seo.php, altrimenti smette di verificare il fallback.
        $this->assertFalse(Lang::has('seo.carrello'), 'La rotta scelta per il fallback ha ora una sua description: cambiare rotta.');

        $html = $this->get(route('carrello'))->assertOk()->getContent();

        $this->assertSame(__('seo.default'), $this->metaContent($html, 'name', 'description'));
    }

    // ============ Open Graph / Twitter ============

    public function test_home_carries_the_open_graph_card(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(__('home.meta_title'), $this->metaContent($html, 'property', 'og:title'));
        $this->assertSame(__('seo.home'), $this->metaContent($html, 'property', 'og:description'));
        $this->assertSame(route('home'), $this->metaContent($html, 'property', 'og:url'));
        $this->assertSame('website', $this->metaContent($html, 'property', 'og:type'));
        $this->assertSame(config('app.name'), $this->metaContent($html, 'property', 'og:site_name'));
        $this->assertSame('it_IT', $this->metaContent($html, 'property', 'og:locale'));
        $this->assertSame('en_GB', $this->metaContent($html, 'property', 'og:locale:alternate'));
        $this->assertNotNull($this->metaContent($html, 'property', 'og:image'));
    }

    public function test_the_og_image_is_absolute_and_big_enough_for_a_large_card(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $image = $this->metaContent($html, 'property', 'og:image');

        // Facebook/LinkedIn scaricano l'immagine da sé: un path relativo non lo
        // risolvono e la card esce senza immagine.
        $this->assertStringStartsWith('http', (string) $image);

        $file = public_path((string) parse_url((string) $image, PHP_URL_PATH));
        $this->assertFileExists($file, "og:image punta a un file inesistente: {$image}");

        [$width, $height] = getimagesize($file);
        $this->assertGreaterThanOrEqual(1200, $width, 'summary_large_image vuole almeno 1200px di larghezza');
        $this->assertGreaterThanOrEqual(630, $height, 'summary_large_image vuole almeno 630px di altezza');
    }

    public function test_home_asks_for_a_large_twitter_card(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame('summary_large_image', $this->metaContent($html, 'name', 'twitter:card'));
    }

    // ============ Canonical ============

    public function test_canonical_points_at_the_current_url(): void
    {
        $html = $this->get(route('holiday'))->assertOk()->getContent();

        $this->assertSame(route('holiday'), $this->linkHref($html, 'canonical'));
    }

    public function test_canonical_drops_the_query_string(): void
    {
        // Filtri e tracking (?utm_source=…) generano infinite URL della stessa
        // pagina: senza canonical pulito Google le indicizza come duplicati.
        $html = $this->get(route('holiday').'?utm_source=newsletter&page=2')->assertOk()->getContent();

        $this->assertSame(route('holiday'), $this->linkHref($html, 'canonical'));
    }

    // ============ hreflang ============

    public function test_home_declares_hreflang_alternates_for_both_locales(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(route('home'), $this->linkHref($html, 'alternate', 'it'));
        $this->assertSame(rtrim(route('home'), '/').'/en', $this->linkHref($html, 'alternate', 'en'));
        // x-default: la versione da servire a chi non parla né it né en.
        $this->assertSame(route('home'), $this->linkHref($html, 'alternate', 'x-default'));
    }

    public function test_the_hreflang_alternates_of_the_home_actually_resolve(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['it', 'en'] as $locale) {
            $href = $this->linkHref($html, 'alternate', $locale);
            $this->assertNotNull($href, "Manca l'alternate hreflang=\"{$locale}\"");

            $path = (string) parse_url($href, PHP_URL_PATH);

            $this->reloadRoutesFor($href);
            $this->get($path === '' ? '/' : $path)
                ->assertOk();
        }
    }

    public function test_hreflang_uses_the_translated_slug_of_the_other_locale(): void
    {
        // La trappola: costruire l'alternate concatenando "/en" all'URL italiano
        // darebbe /en/chi-siamo, che in inglese non esiste (è /en/about-us).
        $html = $this->get(route('about'))->assertOk()->getContent();

        $english = (string) $this->linkHref($html, 'alternate', 'en');
        $this->assertStringEndsWith('/en/about-us', $english);

        $this->reloadRoutesFor($english);
        $this->get((string) parse_url($english, PHP_URL_PATH))->assertOk();
    }

    public function test_hreflang_keeps_the_route_parameters(): void
    {
        // Le pagine con parametro (regione, articolo, evento, token) sono la
        // maggioranza del sito: un alternate che perde il parametro punta altrove.
        // Qui serve una rotta con parametro NON legato a un model, altrimenti il
        // test parlerebbe di seeding invece che di hreflang.
        $url = route('password.reset', ['token' => 'abc123']);

        $html = $this->get((string) parse_url($url, PHP_URL_PATH))->assertOk()->getContent();

        $english = (string) $this->linkHref($html, 'alternate', 'en');
        $this->assertStringEndsWith('/en/reset-password/abc123', $english);
    }

    // ============ Variante inglese ============

    public function test_the_english_home_flips_locale_and_canonical(): void
    {
        $this->reloadRoutesFor('/en');
        $html = $this->get('/en')->assertOk()->getContent();

        // Attenzione: dopo reloadRoutesFor('/en') route('home') È la home
        // inglese — le rotte sono state ri-registrate col prefisso. Le attese
        // si costruiscono da url(), che dà la radice della request in corso.
        $this->assertSame(url('/en'), $this->linkHref($html, 'canonical'));
        $this->assertSame('en_GB', $this->metaContent($html, 'property', 'og:locale'));
        $this->assertSame('it_IT', $this->metaContent($html, 'property', 'og:locale:alternate'));
        $this->assertSame(Lang::get('seo.home', [], 'en'), $this->metaContent($html, 'name', 'description'));
    }

    public function test_the_english_home_keeps_x_default_on_the_italian_version(): void
    {
        $this->reloadRoutesFor('/en');
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(rtrim(url('/'), '/'), $this->linkHref($html, 'alternate', 'x-default'));
    }

    // ============ Area partner: già noindex, non va invitata all'indice ============

    public function test_partner_pages_get_no_canonical_and_no_hreflang(): void
    {
        // NoIndexPartnerPages manda X-Robots-Tag: noindex su partner.*; un
        // canonical o un hreflang sulla stessa pagina direbbe il contrario.
        $this->actingAsActivePartner();

        $response = $this->get(route('partner.dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));
        $this->assertNull($this->linkHref($html, 'canonical'));
        $this->assertNull($this->linkHref($html, 'alternate', 'en'));
        $this->assertNull($this->metaContent($html, 'property', 'og:url'));
    }

    // ============ Parità it/en ============

    public function test_the_two_seo_files_declare_the_same_keys(): void
    {
        // LangParityTest ha l'elenco dei file cablato e non conosce seo.php:
        // la parità di questo file la garantisce il suo stesso test.
        $it = array_keys(require base_path('lang/it/seo.php'));
        $en = array_keys(require base_path('lang/en/seo.php'));

        sort($it);
        sort($en);

        $this->assertSame($it, $en);
    }

    public function test_the_site_default_description_exists_in_both_locales(): void
    {
        $this->assertNotSame('seo.default', Lang::get('seo.default', [], 'it'));
        $this->assertNotSame('seo.default', Lang::get('seo.default', [], 'en'));
    }
}
