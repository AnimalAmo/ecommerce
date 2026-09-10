<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartValidationException;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un acquisto = una struttura (decisione 3). Non è una regola di prodotto: il
 * PaymentIntent nasce sull'account connesso del venditore e non si sposta con
 * un update, quindi due venditori nello stesso ordine sono impagabili.
 */
class SinglePartnerCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_prodotti_dello_stesso_partner_convivono(): void
    {
        $partner = User::factory()->create();
        [$first, $second] = Structure::factory()->count(2)->for($partner)->create();

        $this->actingAs(User::factory()->create());
        $cart = app(CartManager::class);

        $cart->addItem('structure', $first->id, $this->bookingOptions(), isGift: false);
        $cart->addItem('structure', $second->id, $this->bookingOptions(), isGift: false);

        $this->assertSame(2, $cart->count());
        $this->assertSame($partner->id, $cart->currentPartnerUserId());
    }

    public function test_un_prodotto_di_un_altro_partner_viene_rifiutato(): void
    {
        $mine = Structure::factory()->create();
        $other = Structure::factory()->create();

        $this->actingAs(User::factory()->create());
        $cart = app(CartManager::class);
        $cart->addItem('structure', $mine->id, $this->bookingOptions(), isGift: false);

        try {
            $cart->addItem('structure', $other->id, $this->bookingOptions(), isGift: false);
            $this->fail('Attesa CartValidationException per due partner nello stesso carrello.');
        } catch (CartValidationException $exception) {
            $this->assertSame(__('cart.single_partner'), $exception->getMessage());
        }

        $this->assertSame(1, $cart->count());
    }

    public function test_il_vincolo_vale_anche_per_il_guest(): void
    {
        $mine = Structure::factory()->create();
        $other = Structure::factory()->create();

        $cart = app(CartManager::class);
        $cart->addItem('structure', $mine->id, $this->bookingOptions(), isGift: false);

        $this->expectException(CartValidationException::class);

        $cart->addItem('structure', $other->id, $this->bookingOptions(), isGift: false);
    }

    public function test_svuotato_il_carrello_il_vincolo_riparte(): void
    {
        $mine = Structure::factory()->create();
        $other = Structure::factory()->create();

        $this->actingAs(User::factory()->create());
        $cart = app(CartManager::class);
        $cart->addItem('structure', $mine->id, $this->bookingOptions(), isGift: false);
        $cart->clear();

        $item = $cart->addItem('structure', $other->id, $this->bookingOptions(), isGift: false);

        $this->assertSame($other->user_id, $item->partnerUserId);
    }

    private function bookingOptions(): array
    {
        return [
            'check_in' => now()->addWeek()->toDateString(),
            'check_out' => now()->addWeek()->addDays(2)->toDateString(),
        ];
    }
}
