<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    /**
     * Re-load the web routes with the target request bound so mcamara
     * registers that locale's translated slugs (see LocalizationTest for the
     * full explanation): the test harness boots the app in setUp(), before
     * any request exists, so a bare $this->get('/en/...') only ever matches
     * the default (it) slugs and 404s.
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

    public function test_the_customer_terms_page_renders(): void
    {
        // assertSee con $escaped = false: il testo legale è pieno di apostrofi
        // tipografici, che nella pagina sono entità HTML.
        $this->get(route('terms.customers'))
            ->assertOk()
            ->assertSee('Termini e condizioni', false)
            ->assertSee('Definizioni', false)
            // Guardia contro `{!! !!}` che diventasse `{{ }}`: se il corpo
            // venisse escapato, questo tag letterale sparirebbe pur restando
            // vero (in forma escapata) tutto il resto dell'asserzione sopra.
            ->assertSee('<h3 id="1-definizioni">', false);
    }

    public function test_the_supplier_terms_page_renders(): void
    {
        $this->get(route('terms.suppliers'))
            ->assertOk()
            ->assertSee('Premesse', false);
    }

    public function test_the_page_shows_the_last_update_date(): void
    {
        $this->get(route('terms.customers'))->assertSee('26/08/2026', false);
    }

    public function test_a_page_without_a_row_returns_404(): void
    {
        Page::where('slug', Page::TERMS_SUPPLIERS)->delete();

        $this->get(route('terms.suppliers'))->assertNotFound();
    }

    public function test_the_italian_route_has_no_locale_prefix_and_the_english_one_does(): void
    {
        // APP_LOCALE=it, quindi l'italiano è senza prefisso e l'inglese sotto /en.
        $this->assertStringEndsWith('/termini-e-condizioni', route('terms.customers'));

        $this->assertStringContainsString(
            '/en/terms-and-conditions',
            LaravelLocalization::getLocalizedURL('en', route('terms.customers')),
        );
    }

    public function test_the_english_page_renders_the_english_body(): void
    {
        $this->reloadRoutesFor('/en/terms-and-conditions');
        $this->get('/en/terms-and-conditions')
            ->assertOk()
            ->assertSee('Terms and conditions', false)
            ->assertSee('Definitions', false)
            ->assertDontSee('Sezione A', false);
    }

    public function test_the_english_page_warns_that_italian_prevails(): void
    {
        $this->reloadRoutesFor('/en/terms-and-conditions');
        $this->get('/en/terms-and-conditions')
            ->assertSee('only the Italian version is legally binding', false);
    }

    public function test_the_english_supplier_terms_page_renders(): void
    {
        $this->reloadRoutesFor('/en/supplier-terms-and-conditions');
        $this->get('/en/supplier-terms-and-conditions')
            ->assertOk()
            ->assertSee('Supplier general terms of adhesion', false)
            ->assertSee('Recitals', false)
            ->assertDontSee('Premesse', false);
    }

    public function test_the_storefront_footer_links_to_the_customer_terms(): void
    {
        // assertSee su una sottostringa qualsiasi passerebbe anche se il
        // footer puntasse ai fornitori: /termini-e-condizioni è prefisso di
        // /termini-e-condizioni-fornitori. Verifichiamo l'attributo href per
        // intero.
        $this->get(route('home'))->assertSee('href="'.route('terms.customers').'"', false);
    }

    public function test_the_partner_footer_links_to_the_supplier_terms(): void
    {
        $this->get(route('partner.register'))->assertSee('href="'.route('terms.suppliers').'"', false);
    }

    public function test_the_privacy_page_renders(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy', false)
            ->assertSee('Titolare del trattamento', false)
            // Stessa guardia del test sui termini: se il corpo venisse
            // escapato, il tag letterale sparirebbe.
            ->assertSee('<h2 id="1-titolare-del-trattamento">', false)
            ->assertSee('27/08/2026', false);
    }

    public function test_the_english_privacy_page_renders_the_english_body(): void
    {
        $this->reloadRoutesFor('/en/privacy-policy');
        $this->get('/en/privacy-policy')
            ->assertOk()
            ->assertSee('Data Controller', false)
            ->assertSee('only the Italian version is legally binding', false)
            ->assertDontSee('Titolare del trattamento', false);
    }

    /**
     * Tre footer diversi espongono la voce Privacy: quello dello storefront,
     * quello dell'area partner e il minimal delle pagine secondarie — che è
     * poi quello sotto la pagina legale stessa. Il primo giro di modifica ne
     * aveva mancato uno: qui sono elencati tutti e tre.
     */
    public function test_every_footer_links_to_the_privacy_page(): void
    {
        foreach ([route('home'), route('partner.register'), route('contact')] as $url) {
            $this->get($url)->assertSee('href="'.route('privacy').'"', false);
        }
    }

    /**
     * L'informativa cookie non è una pagina nostra: la ospita Iubenda e i tre
     * footer la citano tutti. Restavano tre `href="#"` — un link morto in ogni
     * footer — quindi qui si verifica la destinazione, non la sola etichetta.
     */
    public function test_every_footer_links_to_the_cookie_policy(): void
    {
        // URL letterale, non config(): rileggere la chiave che leggono i blade
        // proverebbe solo che il footer stampa la config, e passerebbe anche
        // con l'id del documento sbagliato.
        $url = 'https://www.iubenda.com/privacy-policy/99317099/cookie-policy';

        $this->assertSame($url, config('services.iubenda.cookie_policy_url'));

        foreach ([route('home'), route('partner.register'), route('contact')] as $page) {
            $this->get($page)
                // e(): il blade passa dalla escape di {{ }}, e una URL con query
                // string uscirebbe con &amp;.
                ->assertSee('href="'.e($url).'"', false)
                // Senza la classe il link porta fuori dal sito invece di aprire
                // il riquadro: è quella a legare l'anchor al loader Iubenda.
                ->assertSee('iubenda-embed', false);
        }
    }

    /**
     * "Gestisci cookie" non è una pagina: la classe è l'aggancio al pannello
     * preferenze del widget consenso. Senza, resta il link morto di prima.
     */
    public function test_the_storefront_footer_reopens_the_cookie_preferences(): void
    {
        $this->get(route('home'))->assertSee('iubenda-cs-preferences-link', false);
    }

    public function test_the_privacy_page_returns_404_without_its_row(): void
    {
        Page::where('slug', Page::PRIVACY)->delete();

        $this->get(route('privacy'))->assertNotFound();
    }
}
