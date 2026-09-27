<?php

namespace Tests\Feature\Cart;

use App\Livewire\Commerce\Cart;
use App\Models\Event\Event;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un carrello ha un solo partner: la modalità si risolve una volta e cambia
 * la riga "Metodo di pagamento sicuro", che per un partner offline mentirebbe.
 */
class CartPayOnSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');

        // La riga «Pagherai direttamente al partner» e le CTA verso il checkout
        // esistono solo quando il percorso è acceso: dal 27/09/2026 nasce spento
        // (config/commerce.php). Questa classe è la specifica di quelle diciture,
        // quindi si accende il flag. Il caso col flag spento — la spiegazione al
        // posto delle CTA — ha il suo test in fondo alla classe.
        config(['commerce.on_site_booking' => true]);

        $this->actingAs(User::factory()->create());
    }

    private function addEventOf(User $owner): void
    {
        $event = Event::factory()->create(['user_id' => $owner->id]);

        app(CartManager::class)->addItem('event', $event->id, ['participants' => 1], false);
    }

    public function test_un_carrello_di_un_partner_offline_dice_che_si_paga_il_partner(): void
    {
        $this->addEventOf(User::factory()->offlinePartner()->create());

        $html = Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('cart.ui.pay_on_site'))
            ->assertDontSee(__('cart.ui.secure_payment'))
            ->html();

        // Riepilogo desktop + barra fissa mobile.
        $this->assertSame(2, substr_count($html, e(__('cart.ui.pay_on_site'))));
    }

    public function test_un_carrello_di_un_partner_online_resta_pagamento_sicuro(): void
    {
        $this->addEventOf(User::factory()->stripeConnected()->create());

        Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('cart.ui.secure_payment'))
            ->assertDontSee(__('cart.ui.pay_on_site'));
    }

    public function test_un_carrello_di_un_partner_offline_non_parla_di_commissioni(): void
    {
        // Chi paga il partner direttamente non paga commissioni ad AnimalAmo.
        $this->addEventOf(User::factory()->offlinePartner()->create());

        Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('cart.ui.taxes_included_on_site'))
            ->assertDontSee(__('cart.ui.taxes_included'));
    }

    public function test_un_carrello_di_un_partner_online_resta_tasse_e_commissioni(): void
    {
        $this->addEventOf(User::factory()->stripeConnected()->create());

        Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('cart.ui.taxes_included'))
            ->assertDontSee(__('cart.ui.taxes_included_on_site'));
    }

    /**
     * Percorso spento, il default di produzione (27/09/2026): al posto della
     * promessa «Pagherai direttamente al partner» e delle due CTA verso il
     * checkout, la spiegazione e il rimando alla scheda. Le righe e il totale
     * restano: il carrello non si svuota, è la richiesta esplicita della cliente.
     */
    public function test_col_percorso_spento_il_carrello_spiega_invece_di_promettere(): void
    {
        config(['commerce.on_site_booking' => false]);

        $event = Event::factory()->create(['user_id' => User::factory()->offlinePartner()->create()->id]);
        app(CartManager::class)->addItem('event', $event->id, ['participants' => 1], false);

        Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('checkout.on_site.unavailable.title'))
            ->assertSee(__('checkout.on_site.unavailable.body'))
            // Il rimando alla scheda pubblica: è lì che stanno i recapiti.
            ->assertSee(__('catalog.book_with_partner'))
            ->assertSeeHtml('href="'.route('eventi.detail', $event->slug).'"')
            ->assertDontSee(__('cart.ui.pay_on_site'))
            ->assertDontSee(__('cart.ui.go_to_checkout'))
            ->assertDontSee(__('cart.ui.proceed_checkout'))
            // La riga del prodotto è ancora lì: il carrello non si svuota.
            ->assertSee($event->title);

        $this->assertCount(1, app(CartManager::class)->items());
    }

    /** Col flag spento un carrello di partner online non cambia di una virgola. */
    public function test_col_percorso_spento_il_carrello_di_un_partner_online_tiene_le_cta(): void
    {
        config(['commerce.on_site_booking' => false]);

        $this->addEventOf(User::factory()->stripeConnected()->create());

        Livewire::test(Cart::class)
            ->assertOk()
            ->assertSee(__('cart.ui.go_to_checkout'))
            ->assertDontSee(__('checkout.on_site.unavailable.title'));
    }
}
