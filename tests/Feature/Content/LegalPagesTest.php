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
            ->assertSee('Definizioni', false);
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
}
