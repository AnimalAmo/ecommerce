<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Re-load the web routes with the target request bound so mcamara
     * registers that locale's translated slugs.
     *
     * In production, route files load *after* the incoming request is bound,
     * so LaravelLocalization::setLocale() sees the /en prefix and registers
     * the English slugs. The test harness boots the app in setUp() — before
     * any request exists — so it only ever registers the default (it) slugs.
     * This helper mirrors the production boot order for a given URL.
     */
    private function reloadRoutesFor(string $uri): void
    {
        $this->app->instance('request', Request::create($uri, 'GET'));

        // The mcamara service is a singleton that captured the request at
        // construction (in setUp, before any URL existed). Rebuild it against
        // the freshly bound request so it reads the /en prefix.
        $this->app->forgetInstance(\Mcamara\LaravelLocalization\LaravelLocalization::class);
        $this->app->forgetInstance('laravellocalization');
        LaravelLocalization::clearResolvedInstance('laravellocalization');
        $loc = app('laravellocalization');
        // Populate the lazily-loaded supportedLocales property before setLocale,
        // otherwise the /en segment is not recognised as a supported locale.
        $loc->getSupportedLocales();
        $loc->setLocale();

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    // ============ Default locale (it): no URL prefix ============

    public function test_default_italian_urls_have_no_prefix_and_respond_200(): void
    {
        $this->get('/carrello')->assertOk();
        $this->get('/chi-siamo')->assertOk();
        $this->get('/preferiti')->assertOk();
    }

    public function test_home_responds_200_without_prefix(): void
    {
        $this->get('/')->assertOk();
    }

    // ============ Non-default locale (en): /en prefix + translated slugs ============

    public function test_english_cart_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/cart');
        $this->get('/en/cart')->assertOk();
    }

    public function test_english_about_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/about-us');
        $this->get('/en/about-us')->assertOk();
    }

    public function test_english_favourites_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/favourites');
        $this->get('/en/favourites')->assertOk();
    }

    public function test_english_partner_dashboard_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/dashboard');
        $this->get('/en/partner/dashboard')->assertOk();
    }

    public function test_english_partner_create_service_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/create-service');
        $this->get('/en/partner/create-service')->assertOk();
    }

    public function test_english_partner_structure_type_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/type');
        $this->get('/en/partner/structure/type')->assertOk();
    }

    public function test_english_partner_hotel_title_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/title');
        $this->get('/en/partner/structure/hotel/title')->assertOk();
    }

    public function test_english_partner_hotel_location_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/location');
        $this->get('/en/partner/structure/hotel/location')->assertOk();
    }

    public function test_english_partner_hotel_description_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/description');
        $this->get('/en/partner/structure/hotel/description')->assertOk();
    }

    public function test_english_partner_hotel_rooms_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/rooms');
        $this->get('/en/partner/structure/hotel/rooms')->assertOk();
    }

    public function test_english_partner_hotel_cancellation_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/cancellation');
        $this->get('/en/partner/structure/hotel/cancellation')->assertOk();
    }

    public function test_english_partner_hotel_services_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/services');
        $this->get('/en/partner/structure/hotel/services')->assertOk();
    }

    public function test_english_partner_hotel_animal_services_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/animal-services');
        $this->get('/en/partner/structure/hotel/animal-services')->assertOk();
    }

    public function test_english_partner_hotel_smartbox_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/smartbox');
        $this->get('/en/partner/structure/hotel/smartbox')->assertOk();
    }

    public function test_english_partner_hotel_photos_url_uses_en_prefix_and_translated_slug(): void
    {
        $this->reloadRoutesFor('/en/partner/structure/hotel/photos');
        $this->get('/en/partner/structure/hotel/photos')->assertOk();
    }

    public function test_english_slug_with_italian_path_is_not_reachable(): void
    {
        // The Italian slug under the /en prefix must not resolve as 200.
        $this->reloadRoutesFor('/en/carrello');
        $response = $this->get('/en/carrello');

        $this->assertNotSame(200, $response->getStatusCode());
    }

    // ============ Route helpers ============

    public function test_route_helper_returns_italian_slug_in_default_locale(): void
    {
        app()->setLocale('it');
        LaravelLocalization::setLocale('it');

        $this->assertStringEndsWith('/carrello', route('carrello'));
    }

    public function test_get_localized_url_builds_english_prefixed_translated_url(): void
    {
        $localized = LaravelLocalization::getLocalizedURL('en', route('carrello'));

        $this->assertStringEndsWith('/en/cart', $localized);
    }

    // ============ Header links preserve the active locale ============

    public function test_english_home_header_links_keep_the_en_prefix(): void
    {
        // Regression: on an /en page, the logo and the "Animal Holiday" nav link
        // were hardcoded to "/" and "/#holiday" — the bare default-locale home.
        // Clicking either silently dropped the /en prefix and reverted to Italian,
        // so the language never persisted across page navigation.
        $this->reloadRoutesFor('/en');
        $response = $this->get('/en');
        $response->assertOk();

        $response->assertDontSee('href="/"', false);
        $response->assertDontSee('href="/#holiday"', false);
        $response->assertSee('/en#holiday', false);
    }

    // ============ Locale switch ============

    public function test_locale_switch_changes_locale_and_redirects(): void
    {
        $response = $this->withHeaders(['referer' => url('/carrello')])
            ->get('/locale/en');

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));
    }

    public function test_locale_switch_ignores_a_cross_origin_referer(): void
    {
        // Referer verso un host esterno: mai redirect fuori dominio (open redirect).
        $response = $this->withHeaders(['referer' => 'https://evil.example.com/phish'])
            ->get('/locale/en');

        $target = $response->headers->get('Location');
        $this->assertSame(request()->getHost(), parse_url($target, PHP_URL_HOST));
    }

    public function test_locale_switch_ignores_an_unsupported_locale(): void
    {
        $response = $this->withHeaders(['referer' => url('/carrello')])
            ->get('/locale/de');

        $response->assertRedirect();
        $this->assertNull(session('locale'));
        $this->assertSame(request()->getHost(), parse_url($response->headers->get('Location'), PHP_URL_HOST));
    }

    // ============ Webhooks stay outside the localized group ============

    public function test_webhooks_are_excluded_from_localization(): void
    {
        // Webhooks must never receive a locale prefix: they are POST (ignored
        // by httpMethodsIgnored) and their path is in urlsIgnored, and they are
        // registered outside the localized route group.
        $this->assertContains('POST', config('laravellocalization.httpMethodsIgnored'));
        $this->assertContains('/webhooks', config('laravellocalization.urlsIgnored'));
        $this->assertContains('/webhooks/*', config('laravellocalization.urlsIgnored'));
    }
}
