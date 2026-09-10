<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartValidationException;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Il proprietario del prodotto viaggia con la riga dal carrello all'ordine:
 * è ciò che permette di instradare l'incasso e di sapere a chi spetta il
 * denaro anche dopo che il prodotto è stato cancellato dal catalogo.
 */
class CartPartnerOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_riga_carrello_porta_il_proprietario_del_prodotto(): void
    {
        $partner = User::factory()->create();
        $structure = Structure::factory()->for($partner)->create();

        $this->actingAs(User::factory()->create());

        $item = app(CartManager::class)->addItem('structure', $structure->id, $this->bookingOptions(), isGift: false);

        $this->assertSame($partner->id, $item->partnerUserId);
        $this->assertDatabaseHas('cart_items', [
            'purchasable_id' => $structure->id,
            'partner_user_id' => $partner->id,
        ]);
    }

    public function test_il_proprietario_viaggia_anche_nel_carrello_guest(): void
    {
        $partner = User::factory()->create();
        $structure = Structure::factory()->for($partner)->create();

        $item = app(CartManager::class)->addItem('structure', $structure->id, $this->bookingOptions(), isGift: false);

        $this->assertSame($partner->id, $item->partnerUserId);
    }

    public function test_un_prodotto_senza_proprietario_non_entra_nel_carrello(): void
    {
        $orphan = Structure::factory()->create(['user_id' => null]);

        $this->actingAs(User::factory()->create());

        // Senza account connesso non c'è header Stripe-Account: il checkout
        // non degraderebbe a commissione zero, esploderebbe (spec §6).
        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(__('cart.product_without_owner'));

        app(CartManager::class)->addItem('structure', $orphan->id, $this->bookingOptions(), isGift: false);
    }

    private function bookingOptions(): array
    {
        return [
            'check_in' => now()->addWeek()->toDateString(),
            'check_out' => now()->addWeek()->addDays(2)->toDateString(),
        ];
    }
}
