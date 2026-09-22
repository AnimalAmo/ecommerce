<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartValidationException;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Cart\SessionCartStorage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regalo solo con pagamento online: una smartbox regalata arriva al
 * destinatario come già pagata, e con un partner che incassa in struttura non
 * l'avrebbe pagata nessuno. Il toggle "Regala" sparisce dal dettaglio e
 * CartManager::addItem rifiuta la riga anche se la chiamata arriva a mano.
 */
class GiftOnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private SmartboxPackage $box;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->seed(DatabaseSeeder::class);

        $this->box = SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail();
    }

    // ── Dettaglio smartbox ───────────────────────────────────────────────────

    public function test_il_dettaglio_di_un_partner_offline_non_mostra_regala(): void
    {
        $this->ownedBy(User::factory()->offlinePartner()->create());

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-lombardia'])
            ->assertOk()
            ->assertDontSeeHtml('setGift(true)')
            ->assertDontSeeHtml('setGift(false)');
    }

    public function test_il_link_regalo_verso_un_partner_offline_riparte_da_acquista(): void
    {
        $this->ownedBy(User::factory()->offlinePartner()->create());

        Livewire::withQueryParams(['regalo' => '1'])
            ->test(SmartboxDetail::class, ['box' => 'relax-lombardia'])
            ->assertSet('gift', false)
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $entry = array_values(session()->get(SessionCartStorage::SESSION_KEY, []))[0];
        $this->assertFalse($entry['is_gift']);
    }

    public function test_il_dettaglio_di_un_partner_online_mostra_ancora_regala(): void
    {
        $this->ownedBy(User::factory()->stripeConnected()->create());

        Livewire::withQueryParams(['regalo' => '1'])
            ->test(SmartboxDetail::class, ['box' => 'relax-lombardia'])
            ->assertSet('gift', true)
            ->assertSeeHtml('setGift(true)')
            ->assertSeeHtml('setGift(false)');
    }

    // ── CartManager ──────────────────────────────────────────────────────────

    public function test_add_item_rifiuta_il_regalo_di_un_partner_offline(): void
    {
        $this->ownedBy(User::factory()->offlinePartner()->create());

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(__('cart.gift_requires_online_payment'));

        app(CartManager::class)->addItem('smartbox_package', $this->box->id, $this->giftOptions(), isGift: true);
    }

    public function test_add_item_accetta_l_acquisto_per_se_da_un_partner_offline(): void
    {
        $this->ownedBy(User::factory()->offlinePartner()->create());

        $item = app(CartManager::class)->addItem('smartbox_package', $this->box->id, ['animals' => ['cane' => 1]], isGift: false);

        $this->assertFalse($item->isGift);
        $this->assertSame(21500, $item->priceCents);
    }

    public function test_add_item_accetta_ancora_il_regalo_di_un_partner_online(): void
    {
        $this->ownedBy(User::factory()->stripeConnected()->create());

        $item = app(CartManager::class)->addItem('smartbox_package', $this->box->id, $this->giftOptions(), isGift: true);

        $this->assertTrue($item->isGift);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function ownedBy(User $owner): void
    {
        $this->box->forceFill(['user_id' => $owner->id])->save();
    }

    private function giftOptions(): array
    {
        return [
            'animals' => ['cane' => 1],
            'gift' => ['dedication' => null, 'message' => null, 'recipient_email' => null],
        ];
    }
}
