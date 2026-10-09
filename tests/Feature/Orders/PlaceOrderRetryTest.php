<?php

namespace Tests\Feature\Orders;

use App\Actions\Order\PlaceOrderAction;
use App\Data\Checkout\OrderPipelineData;
use App\Livewire\Commerce\Checkout;
use App\Mail\OrderConfirmationMail;
use App\Mail\PartnerNewBookingMail;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Pipes\Order\ClearCartPipe;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Closure;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Deadlock nella transaction dell'ordine: si ritenta tutto prima dello storno.
 * Niente RefreshDatabase: Laravel ritenta solo la transaction più esterna, e
 * quella del test la renderebbe annidata. Il db sqlite in memoria nasce
 * vuoto con ogni applicazione di test: basta migrarlo in setUp.
 * Il deadlock è simulato nell'ultima pipe (ClearCartPipe), dopo che ordine,
 * righe, posti evento e pagamento (con OrderPaid) sono già stati scritti.
 */
class PlaceOrderRetryTest extends TestCase
{
    use PlacesOrders;

    /** Tentativi della pipeline che finiscono in deadlock (-1 = sempre). */
    private int $deadlocks = 0;

    private int $attempts = 0;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        Mail::fake();

        $this->seed(PaymentGatewaySeeder::class);
        app(PaymentGatewayService::class)->clearCache();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(StripeGateway::class, $this->gateway);

        $test = $this;
        $this->app->bind(ClearCartPipe::class, fn ($app) => new class($app->make(CartManager::class), $test) extends ClearCartPipe
        {
            public function __construct(CartManager $cart, private readonly PlaceOrderRetryTest $test)
            {
                parent::__construct($cart);
            }

            public function handle(OrderPipelineData $data, Closure $next): mixed
            {
                return parent::handle($data, function (OrderPipelineData $data) use ($next): mixed {
                    $this->test->maybeDeadlock();

                    return $next($data);
                });
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Chiamata dalla pipe finta: il tentativo n-esimo fallisce come un deadlock MySQL. */
    public function maybeDeadlock(): void
    {
        $this->attempts++;

        if ($this->deadlocks < 0 || $this->attempts <= $this->deadlocks) {
            throw new DeadlockException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction');
        }
    }

    public function test_a_deadlock_is_retried_and_the_order_is_placed_once_without_refund(): void
    {
        $this->deadlocks = 1;
        $this->actingAs($this->buyer());
        [$room, $event] = $this->fillCart();

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3);

        $this->assertSame(2, $this->attempts);
        $this->assertSame([], $this->gateway->refundCalls);

        // Niente avanzi del primo tentativo: un ordine, due righe, un pagamento.
        $order = Order::sole();
        $this->assertCount(2, $order->items);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('order_payments', 1);
        $this->assertSame(1, OrderItem::query()->where('room_id', $room->id)->count());
        $this->assertSame(2, $event->refresh()->booked_participants);

        // OrderPaid del tentativo fallito scartato col rollback: mail una volta sola.
        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(PartnerNewBookingMail::class, 1);
        $this->assertTrue($this->cart()->items()->isEmpty());
    }

    public function test_a_deadlock_on_every_attempt_still_ends_in_the_refund(): void
    {
        $this->deadlocks = -1;
        $this->actingAs($this->buyer());
        [, $event] = $this->fillCart();
        $totalCents = $this->cart()->total();

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2);

        $this->assertSame(PlaceOrderAction::TRANSACTION_ATTEMPTS, $this->attempts);
        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => $totalCents, 'stripe_account_id' => $this->seller()->partnerProfile->stripe_account_id]],
            $this->gateway->refundCalls,
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $event->refresh()->booked_participants);
        Mail::assertNothingSent();
    }

    public function test_an_on_site_booking_is_retried_too(): void
    {
        $this->deadlocks = 1;
        $this->actingAs($this->buyer());
        $structure = Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000, 'animal_supplement_cents' => 0]);
        $room = Room::factory()->for($structure)->create(['name' => 'Camera Vista Mare']);
        $this->addRoomLine($room);

        $order = $this->placeOnSiteOrder();

        $this->assertSame(2, $this->attempts);
        $this->assertSame($order->id, Order::sole()->id);
        $this->assertSame($room->id, $order->items()->sole()->room_id);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buyer(): User
    {
        return User::factory()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia@example.com',
            'phone' => '340 5738920',
        ]);
    }

    /**
     * Carrello del venditore Online: una stanza (unità 1) e un evento da 2 posti.
     *
     * @return array{0: Room, 1: Event}
     */
    private function fillCart(): array
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000, 'animal_supplement_cents' => 0]);
        $room = Room::factory()->for($structure)->create(['name' => 'Camera Vista Mare', 'price_cents' => 8000]);
        $this->addRoomLine($room);

        $event = Event::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        return [$room, $event];
    }

    private function addRoomLine(Room $room): void
    {
        $this->cart()->addItem('structure', $room->structure_id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'room_id' => $room->id,
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false);
    }
}
