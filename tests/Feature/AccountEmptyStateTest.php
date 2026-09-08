<?php

namespace Tests\Feature;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

/**
 * Le pagine "account" (carrello, preferiti, i miei ordini) con il catalogo VUOTO,
 * cioè animalamo.it il giorno del lancio: niente strutture, eventi, cofanetti né
 * preferiti da cui pescare suggerimenti. Devono restare oneste — nessuna cornice
 * di carosello sopra zero card, nessuna pagina che sembra caricata a metà — e le
 * CTA devono portare dove il locale corrente si aspetta.
 */
class AccountEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Semina SOLO i seeder di piattaforma, come in produzione: regioni, pagine
     * legali e articoli. Nessun prodotto, quindi nessun preferito e nessun
     * suggerimento (FavoriteService::topFavorited() torna []).
     */
    private function seedProductionCatalogue(): void
    {
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Ricarica le rotte con la request di destinazione già bindata, così mcamara
     * registra gli slug tradotti di quel locale (stesso helper di LocalizationTest:
     * in test l'app parte prima che esista una request, e vedrebbe solo l'italiano).
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

    // ============ /profilo/i-miei-ordini ============

    public function test_orders_page_shows_an_empty_state_when_the_user_has_no_orders(): void
    {
        $this->seedProductionCatalogue();

        // Il caso di ogni utente appena registrato: senza stato vuoto la pagina
        // finisce con le tab e poi il nulla, e sembra caricata a metà.
        $this->actingAs(User::factory()->create())
            ->get(route('profilo.ordini'))
            ->assertOk()
            ->assertSee(__('profile.orders_empty'))
            ->assertSee(__('profile.orders_empty_cta'))
            ->assertSee('href="'.route('news').'"', false);
    }

    public function test_orders_page_shows_the_empty_state_on_the_past_tab_too(): void
    {
        $this->seedProductionCatalogue();

        $this->actingAs(User::factory()->create())
            ->get(route('profilo.ordini', ['tab' => 'passati']))
            ->assertOk()
            ->assertSee(__('profile.orders_empty'));
    }

    public function test_orders_page_hides_the_empty_state_when_an_order_exists(): void
    {
        $this->seedProductionCatalogue();

        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();
        OrderItem::factory()->for($order)->create([
            'booked_from' => now()->addDays(5),
            'booked_until' => now()->addDays(10),
        ]);

        $this->actingAs($user)
            ->get(route('profilo.ordini'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertDontSee(__('profile.orders_empty'));
    }

    // ============ /carrello — carosello "Le attività più amate" ============

    public function test_empty_cart_does_not_render_the_carousel_over_zero_cards(): void
    {
        $this->seedProductionCatalogue();

        // Senza suggerimenti il blocco desktop mostrava titolo, riga e le due
        // frecce sopra una griglia vuota: cornice di un carosello inesistente.
        $this->get(route('carrello'))
            ->assertOk()
            ->assertSee(__('cart.ui.empty_heading'))
            ->assertDontSee(__('cart.ui.most_loved'))
            ->assertDontSee(__('cart.ui.prev_cards'))
            ->assertDontSee(__('cart.ui.next_cards'));
    }

    public function test_empty_cart_still_renders_the_carousel_when_there_are_suggestions(): void
    {
        // Catalogo demo: i preferiti seminati alimentano topFavorited().
        $this->seed(DatabaseSeeder::class);

        $this->get(route('carrello'))
            ->assertOk()
            ->assertSee(__('cart.ui.most_loved'))
            ->assertSee(__('cart.ui.prev_cards'))
            ->assertSee(__('cart.ui.next_cards'));
    }

    // ============ CTA degli stati vuoti: route(), non url() ============

    public function test_empty_cart_ctas_keep_the_locale_prefix(): void
    {
        $this->seedProductionCatalogue();
        $this->reloadRoutesFor('/en/cart');

        // Sanity: le rotte caricate sono davvero quelle inglesi.
        $this->assertStringContainsString('/en/', route('eventi'));

        $this->get('/en/cart')
            ->assertOk()
            ->assertSee('href="'.route('eventi').'"', false)
            // url('/eventi') buttava il visitatore inglese sulla pagina italiana.
            ->assertDontSee('href="'.url('/eventi').'"', false)
            // Con il catalogo vuoto /eventi è a sua volta vuoto: seconda CTA su /news, che ha contenuto vero.
            ->assertSee('href="'.route('news').'"', false);
    }

    public function test_empty_favorites_ctas_keep_the_locale_prefix(): void
    {
        $this->seedProductionCatalogue();
        $this->reloadRoutesFor('/en/favourites');

        $this->assertStringContainsString('/en/', route('holiday'));

        $this->get('/en/favourites')
            ->assertOk()
            ->assertSee('href="'.route('holiday').'"', false)
            ->assertDontSee('href="'.url('/animal-holiday').'"', false)
            ->assertSee('href="'.route('news').'"', false);
    }
}
