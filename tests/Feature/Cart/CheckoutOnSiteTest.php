<?php

namespace Tests\Feature\Cart;

use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Events\OnSiteOrderConfirmed;
use App\Livewire\Commerce\Checkout;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Closure;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Livewire;
use ReflectionProperty;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Checkout con venditore offline: allo step 2 nessuna sessione Stripe, modalità
 * e token bloccati, conferma senza capture. Il FakePaymentGateway è bindato
 * apposta: nel ramo in struttura non deve ricevere nessuna chiamata.
 */
class CheckoutOnSiteTest extends TestCase
{
    use RefreshDatabase;

    private ?User $seller = null;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        Mail::fake();
        Events::fake([OnSiteOrderConfirmed::class]);

        $this->seed(PaymentGatewaySeeder::class);
        app(PaymentGatewayService::class)->clearCache();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(StripeGateway::class, $this->gateway);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_an_offline_seller_locks_the_on_site_mode_without_a_stripe_session(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertSet('paymentMode', OrderPaymentMode::OnSite->value)
            ->assertSet('clientSecret', null)
            ->assertSet('paymentIntentId', null);

        $this->assertSame(26, strlen((string) $component->get('checkoutToken')));
        $this->assertSame([], $this->gateway->initCalls);
    }

    public function test_confirm_booking_places_a_confirmed_order_and_reaches_step_three(): void
    {
        $buyer = $this->buyer();
        $this->actingAs($buyer);
        $structure = $this->offlineStructure();
        $this->addStructureLine($structure); // 5 notti = 500 €

        $component = Livewire::test(Checkout::class)->call('goToStep', 2);
        $token = $component->get('checkoutToken');

        $component->call('confirmBooking')
            ->assertSet('step', 3)
            ->assertDispatched('cart-updated')
            ->assertSee($structure->name);

        $order = Order::sole();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(OrderPaymentMode::OnSite, $order->payment_mode);
        $this->assertSame($token, $order->checkout_token);
        $this->assertSame('https://example.com/paga', $order->partner_payment_url);
        $this->assertTrue($order->user->is($buyer));
        $this->assertSame(50000, $order->total_cents);
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertTrue($this->cart()->items()->isEmpty());

        // Nessun denaro: il gateway non è mai stato toccato, nemmeno per uno storno.
        $this->assertSame([], $this->gateway->initCalls);
        $this->assertSame([], $this->gateway->captureCalls);
        $this->assertSame([], $this->gateway->refundCalls);

        Events::assertDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_a_guest_with_an_offline_seller_is_sent_to_the_login(): void
    {
        $this->addStructureLine($this->offlineStructure()); // carrello di sessione guest

        Livewire::test(Checkout::class)
            ->set('firstName', 'Mario')
            ->set('lastName', 'Verdi')
            ->set('email', 'mario@example.com')
            ->call('goToStep', 2)
            ->assertSet('step', 1)
            ->assertSet('checkoutToken', null)
            ->assertDispatched('toast-show', $this->toast(__('checkout.on_site.login_required')))
            ->assertDispatched('modal-show', name: 'login');

        $this->assertSame([], $this->gateway->initCalls);
    }

    public function test_confirm_booking_is_ignored_outside_step_two(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        Livewire::test(Checkout::class)
            ->call('confirmBooking')
            ->assertSet('step', 1);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirm_booking_is_ignored_for_an_online_checkout(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => User::factory()->stripeConnected()->create()->id, 'price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('paymentMode', OrderPaymentMode::Online->value)
            ->assertSet('checkoutToken', null)
            ->call('confirmBooking')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirm_booking_is_ignored_while_processing(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->set('processing', true)
            ->call('confirmBooking')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirm_booking_requires_an_authenticated_user(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        $component = Livewire::test(Checkout::class)->call('goToStep', 2);

        Auth::logout();

        $component->call('confirmBooking')
            ->assertSet('step', 2)
            ->assertDispatched('toast-show', $this->toast(__('checkout.on_site.login_required')))
            ->assertDispatched('modal-show', name: 'login');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirm_booking_is_rate_limited_per_user(): void
    {
        $buyer = $this->buyer();
        $this->actingAs($buyer);
        $this->addStructureLine($this->offlineStructure());

        $component = Livewire::test(Checkout::class)->call('goToStep', 2);

        foreach (range(1, 5) as $attempt) {
            RateLimiter::hit('checkout-on-site|'.$buyer->id, 60);
        }

        $component->call('confirmBooking')
            ->assertSet('step', 2)
            ->assertDispatched('toast-show', $this->toast(__('checkout.on_site.throttle', ['seconds' => 60])));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_seller_gone_online_mid_checkout_reopens_step_two_with_stripe(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('paymentMode', OrderPaymentMode::OnSite->value);

        // Il partner collega Stripe e passa a "online" fra lo step 2 e il click.
        $this->offlineSeller()->partnerProfile()->update([
            'online_payment' => true,
            'stripe_account_id' => 'acct_flippedonline0001',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
        ]);
        // In produzione ogni chiamata Livewire è una richiesta nuova: qui il
        // container è lo stesso, e il service scoped terrebbe la vecchia modalità.
        $this->app->forgetScopedInstances();

        $component->call('confirmBooking')
            ->assertDispatched('toast-show', $this->toast(__('checkout.on_site.mode_changed')))
            ->assertSet('step', 2)
            ->assertSet('paymentMode', OrderPaymentMode::Online->value)
            ->assertSet('checkoutToken', null)
            ->assertSet('clientSecret', 'cs_fake_secret');

        $this->assertCount(1, $this->gateway->initCalls);
        $this->assertSame('acct_flippedonline0001', $this->gateway->initCalls[0]['context']['stripe_account_id']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_stripe_actions_are_refused_on_site(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('processPayment')
            ->assertNotDispatched('process-payment')
            ->assertSet('processing', false)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->call('selectPayment', 'apple_pay')
            ->call('selectSavedCard', false);

        $this->assertSame([], $this->gateway->initCalls);
        $this->assertSame([], $this->gateway->captureCalls);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_sold_out_on_confirm_shows_the_toast_without_refunds(): void
    {
        $this->actingAs($this->buyer());
        $event = Event::factory()->create([
            'user_id' => $this->offlineSeller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        $component = Livewire::test(Checkout::class)->call('goToStep', 2);

        $event->update(['booked_participants' => 9]);

        $component->call('confirmBooking')
            ->assertSet('step', 2)
            ->assertDispatched('toast-show', $this->toast('Non ci sono abbastanza posti disponibili.'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], $this->gateway->refundCalls);
        $this->assertCount(1, $this->cart()->items());
    }

    public function test_a_second_confirm_is_idempotent(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->offlineStructure());

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('confirmBooking')
            ->assertSet('step', 3);

        // Secondo click arrivato dopo il primo (step manomesso, carrello già vuoto).
        $component->set('step', 2)
            ->call('confirmBooking')
            ->assertRedirect(route('profilo.ordini'));

        $this->assertSame(1, Order::count());
        Events::assertDispatchedTimes(OnSiteOrderConfirmed::class, 1);
    }

    public function test_a_gift_left_in_the_cart_of_a_seller_gone_offline_is_stopped(): void
    {
        $this->actingAs($this->buyer());
        $seller = User::factory()->stripeConnected()->create();
        $this->cart()->addItem('smartbox_package', SmartboxPackage::factory()->create([
            'user_id' => $seller->id, 'price_cents' => 21500])->id, [
                'animals' => ['cane' => 1],
                'gift' => ['dedication' => 'Marco', 'message' => 'Tanti auguri!'],
            ], true);

        // Regalo messo in carrello quando il partner era online; poi è passato offline.
        $seller->partnerProfile()->update(['online_payment' => false]);
        // in produzione è una richiesta nuova: il service scoped ricorderebbe la modalità letta da addItem
        $this->app->forgetScopedInstances();

        Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->set('recipientEmail', 'marco@example.com')
            ->call('goToStep', 2)
            ->assertSet('step', 1)
            ->assertDispatched('toast-show', $this->toast(__('payment.errors.seller_unavailable')));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], $this->gateway->initCalls);
    }

    public function test_a_gift_flag_set_after_step_two_never_books_the_gift_lines(): void
    {
        $this->actingAs($this->buyer());
        $seller = User::factory()->stripeConnected()->create();
        $this->addStructureLine(Structure::factory()->create(['user_id' => $seller->id, 'price_cents' => 10000]));
        $this->cart()->addItem('smartbox_package', SmartboxPackage::factory()->create([
            'user_id' => $seller->id, 'price_cents' => 21500])->id, [
                'animals' => ['cane' => 1],
                'gift' => ['dedication' => 'Marco', 'message' => 'Tanti auguri!'],
            ], true);

        // Righe messe in carrello quando il partner era online; poi è passato offline.
        $seller->partnerProfile()->update(['online_payment' => false]);
        $this->app->forgetScopedInstances();

        // Lo step 2 si apre sul flusso normale, poi il client ribalta ?regalo.
        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('paymentMode', OrderPaymentMode::OnSite->value)
            ->set('gift', true)
            ->call('confirmBooking')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(2, $this->cart()->items());
    }

    public function test_payment_mode_and_checkout_token_are_locked(): void
    {
        foreach (['paymentMode', 'checkoutToken'] as $property) {
            $this->assertNotEmpty(
                (new ReflectionProperty(Checkout::class, $property))->getAttributes(Locked::class),
                "{$property} deve essere #[Locked]: il client non può dettarla.",
            );
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    private function buyer(): User
    {
        return User::factory()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia@example.com',
            'phone' => '340 5738920',
        ]);
    }

    private function offlineSeller(): User
    {
        if ($this->seller === null) {
            $this->seller = User::factory()->offlinePartner()->create();
            $this->seller->partnerProfile()->update([
                'business_name' => 'Agriturismo Il Faro',
                'payment_url' => 'https://example.com/paga',
            ]);
        }

        return $this->seller;
    }

    private function offlineStructure(): Structure
    {
        return Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]);
    }

    /** Riga struttura: 01/08 → 06/08 (5 notti), 2 adulti e 1 cane. */
    private function addStructureLine(Structure $structure): int|string
    {
        return $this->cart()->addItem('structure', $structure->id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }
}
