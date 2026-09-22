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
}
