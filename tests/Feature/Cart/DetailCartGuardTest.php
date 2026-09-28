<?php

namespace Tests\Feature\Cart;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\SmartboxDetail;
use App\Livewire\Commerce\Checkout;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\User;
use App\Services\Cart\SessionCartStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Difetto C7 (audit 27/09/2026, corretto il 28/09/2026): la guardia sul
 * pagamento diretto nelle cinque schede, ora in un solo punto
 * (AddsCatalogProductToCart).
 *
 * La sezione «Difetto C7» di AddToCartTest prova i cinque rifiuti. Qui i
 * controlli positivi che li rendono significativi — la stessa fixture con un
 * titolare online entra — e il confine della guardia: guarda la modalità di
 * incasso, non Stripe. Il venditore online ma non pagabile lo ferma il
 * checkout allo step 1 (difetto C10), non la scheda.
 *
 * Scritto dal tester il 28/09/2026.
 */
class DetailCartGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Il controllo positivo del test sull'attività di AddToCartTest: la stessa
     * attività, con un titolare che incassa online, entra. Senza, il rifiuto
     * poteva venire dalla fixture (posti, durata) e non dalla guardia.
     */
    public function test_la_stessa_attivita_di_un_titolare_online_entra_in_carrello(): void
    {
        $activity = Event::factory()->activity(3)->create([
            'slug' => 'attivita-online',
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'price_cents' => 11800,
        ]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertDispatched('cart-updated')
            ->assertSet('cartPopupOpen', true);

        $this->assertCount(1, session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /** Il controllo positivo della smartbox: stessa scheda, titolare online, entra. */
    public function test_la_stessa_smartbox_di_un_titolare_online_entra_in_carrello(): void
    {
        $box = SmartboxPackage::factory()->create([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'price_cents' => 21500,
        ]);

        Livewire::test(SmartboxDetail::class, ['box' => $box->slug])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertDispatched('cart-updated')
            ->assertSet('cartPopupOpen', true);

        $this->assertCount(1, session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /** Il regalo verso chi incassa in struttura tiene il suo messaggio, non quello generico. */
    public function test_il_regalo_verso_chi_incassa_in_struttura_dice_perche(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $owner->id, 'price_cents' => 21500]);

        $page = Livewire::withQueryParams(['regalo' => '1'])
            ->test(SmartboxDetail::class, ['box' => $box->slug])
            ->assertSet('gift', true);

        $owner->partnerProfile->update(['online_payment' => false]);
        $this->app->forgetScopedInstances();

        $page->call('addToCart')
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.gift_requires_online_payment'))
            ->assertNotDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable'))
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /**
     * Il confine della guardia. Chi è online ma non pagabile non incassa in
     * struttura: la scheda lo lascia in carrello, e il checkout lo dice già
     * all'apertura dello step 1, prima dei dati personali (C10). Se un giorno
     * la CTA sparirà anche per lui (l'audit lo proponeva per C10), questo test
     * va cambiato insieme alla scheda.
     */
    public function test_chi_e_online_ma_non_pagabile_lo_ferma_il_checkout_non_la_scheda(): void
    {
        $seller = User::factory()->create();
        PartnerProfile::factory()->for($seller)->create();
        $event = Event::factory()->create([
            'slug' => 'evento-venditore-non-pagabile',
            'user_id' => $seller->id,
            'price_cents' => 2500,
        ]);

        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', true);

        Livewire::test(Checkout::class)
            ->assertSet('step', 1)
            ->assertSee(__('checkout.seller_not_payable.notice'));
    }
}
