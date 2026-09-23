<?php

namespace Tests\Feature\Orders;

use App\Actions\Order\PlaceOrderAction;
use App\Data\Checkout\PlaceOrderData;
use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Events\OnSiteOrderConfirmed;
use App\Events\OrderPaid;
use App\Exceptions\CartValidationException;
use App\Exceptions\OrderAlreadyPlacedException;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\TestCase;

/**
 * Pipeline delle prenotazioni "paga in struttura": ordine Confirmed senza
 * pagamento né registro payout, posti evento consumati come online,
 * idempotenza sul token del checkout, guardie su righe e totale.
 */
class PlaceOnSiteOrderTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    private const TOKEN = '01J8Z3K4M5N6P7Q8R9S0T1V2W3';

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        Mail::fake();
        // Solo i due eventi d'ordine: quelli Eloquent (order_number in creating) devono girare davvero.
        Events::fake([OnSiteOrderConfirmed::class, OrderPaid::class]);

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_on_site_order_is_confirmed_without_payment_nor_payouts(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]);
        $this->addStructureLine($structure); // 5 notti = 500 €

        $order = $this->placeOnSiteOrder(checkoutToken: self::TOKEN, partnerPaymentUrl: 'https://example.com/paga');
        $order->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(OrderPaymentMode::OnSite, $order->payment_mode);
        $this->assertTrue($order->isOnSite());
        $this->assertSame(self::TOKEN, $order->checkout_token);
        $this->assertSame('https://example.com/paga', $order->partner_payment_url);
        $this->assertSame(50000, $order->total_cents);
        $this->assertFalse($order->is_gift);
        $this->assertSame(50000, $order->items()->sole()->price_cents);

        // Nessun denaro è passato dalla piattaforma: niente pagamento, niente registro dei rilasci.
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertDatabaseCount('order_payouts', 0);

        $this->assertTrue($this->cart()->items()->isEmpty());

        Events::assertDispatched(OnSiteOrderConfirmed::class, fn (OnSiteOrderConfirmed $event): bool => $event->order->is($order));
        Events::assertNotDispatched(OrderPaid::class);
    }

    public function test_on_site_event_order_consumes_seats(): void
    {
        $event = $this->offlineEvent();
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        $this->placeOnSiteOrder();

        $this->assertSame(2, $event->refresh()->booked_participants);
    }

    public function test_the_same_token_never_books_twice(): void
    {
        $event = $this->offlineEvent();
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);
        $first = $this->placeOnSiteOrder(checkoutToken: self::TOKEN);

        // Replay dello stesso snapshot con le righe di nuovo in carrello: stesso token, nessun secondo ordine.
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        try {
            $this->placeOnSiteOrder(checkoutToken: self::TOKEN);
            $this->fail('Attesa OrderAlreadyPlacedException sul token già usato.');
        } catch (OrderAlreadyPlacedException $exception) {
            $this->assertTrue($exception->order->is($first));
        }

        $this->assertSame(1, Order::count());
        $this->assertSame(2, $event->refresh()->booked_participants);
        $this->assertFalse($this->cart()->items()->isEmpty());
        Events::assertDispatchedTimes(OnSiteOrderConfirmed::class, 1);
    }

    public function test_a_replay_after_the_cart_was_emptied_is_already_placed_not_an_error(): void
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]));
        $this->placeOnSiteOrder(checkoutToken: self::TOKEN);

        // Il doppio click arriva col carrello già svuotato dal primo: esito idempotente, non "righe vuote".
        $this->expectException(OrderAlreadyPlacedException::class);

        $this->placeOnSiteOrder(checkoutToken: self::TOKEN, itemsOverride: collect(), totalCentsOverride: 0);
    }

    public function test_a_second_tab_that_read_the_cart_before_the_first_booking_books_nothing(): void
    {
        $event = $this->offlineEvent();
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        // Due tab dello stesso utente, ognuna col suo token, leggono lo stesso carrello.
        $snapshot = $this->cart()->items();
        $total = $this->cart()->total();

        $first = $this->placeOnSiteOrder(itemsOverride: $snapshot, totalCentsOverride: $total);

        // La seconda entra nella transaction dopo il commit della prima: le sue righe non sono più in carrello.
        try {
            $this->placeOnSiteOrder(itemsOverride: $snapshot, totalCentsOverride: $total);
            $this->fail('Attesa CartValidationException: le righe le ha già prenotate la prima tab.');
        } catch (CartValidationException $exception) {
            $this->assertSame(__('cart.changed_elsewhere'), $exception->getMessage());
        }

        $this->assertTrue(Order::sole()->is($first));
        $this->assertSame(2, $event->refresh()->booked_participants);
        Events::assertDispatchedTimes(OnSiteOrderConfirmed::class, 1);
    }

    public function test_an_order_already_holding_the_token_wins(): void
    {
        $existing = Order::factory()->onSite()->create(['checkout_token' => self::TOKEN]);
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]));

        try {
            $this->placeOnSiteOrder(checkoutToken: self::TOKEN);
            $this->fail('Attesa OrderAlreadyPlacedException.');
        } catch (OrderAlreadyPlacedException $exception) {
            $this->assertTrue($exception->order->is($existing));
        }

        $this->assertSame(1, Order::count());
        $this->assertCount(1, $this->cart()->items());
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_a_sold_out_caused_by_the_same_token_is_already_placed(): void
    {
        $event = $this->offlineEvent();
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        // Il primo click ha preso gli ultimi posti e ha committato mentre il
        // secondo, con lo stesso token, aspettava il lock della reserve.
        $first = Order::factory()->onSite()->create(['checkout_token' => self::TOKEN]);
        $event->update(['booked_participants' => 9]);

        $this->app->instance(PlaceOrderAction::class, new class extends PlaceOrderAction
        {
            private bool $searched = false;

            public function findOnSiteOrder(string $checkoutToken): ?Order
            {
                // La ricerca dentro la transaction arriva prima di quel commit.
                if (! $this->searched) {
                    $this->searched = true;

                    return null;
                }

                return parent::findOnSiteOrder($checkoutToken);
            }
        });

        try {
            $this->placeOnSiteOrder(checkoutToken: self::TOKEN);
            $this->fail('Attesa OrderAlreadyPlacedException, non "posti esauriti".');
        } catch (OrderAlreadyPlacedException $exception) {
            $this->assertTrue($exception->order->is($first));
        }

        $this->assertSame(1, Order::count());
        $this->assertSame(9, $event->refresh()->booked_participants);
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_an_on_site_order_without_lines_is_refused(): void
    {
        try {
            $this->placeOnSiteOrder(itemsOverride: collect(), totalCentsOverride: 0);
            $this->fail('Atteso RuntimeException per ordine senza righe.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no lines', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_an_on_site_total_mismatch_aborts_before_any_write(): void
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]));

        try {
            $this->placeOnSiteOrder(totalCentsOverride: $this->cart()->total() + 100);
            $this->fail('Atteso RuntimeException per totale disallineato.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('mismatch', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(1, $this->cart()->items());
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_an_on_site_sold_out_rolls_back_everything(): void
    {
        $event = $this->offlineEvent();
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);
        $event->update(['booked_participants' => 9]);

        $this->expectException(CartValidationException::class);

        try {
            $this->placeOnSiteOrder();
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertSame(9, $event->refresh()->booked_participants);
            Events::assertNotDispatched(OnSiteOrderConfirmed::class);
        }
    }

    public function test_an_online_capture_without_lines_is_refused(): void
    {
        try {
            $this->placeOrder(itemsOverride: collect(), totalCentsOverride: 0);
            $this->fail('Atteso RuntimeException per ordine online senza righe.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no lines', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_online_data_without_the_capture_is_refused(): void
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]));

        $this->expectException(InvalidArgumentException::class);

        app(PlaceOrderAction::class)->execute(new PlaceOrderData(
            firstName: 'Giulia',
            lastName: 'Rossi',
            email: 'giulia.rossi@gmail.com',
            phone: null,
            country: 'Italia',
            gift: false,
            paymentMethod: null,
            capture: null,
            items: $this->cart()->items(),
            totalCents: $this->cart()->total(),
        ));
    }

    public function test_on_site_data_without_the_token_is_refused(): void
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]));

        $this->expectException(InvalidArgumentException::class);

        app(PlaceOrderAction::class)->execute(new PlaceOrderData(
            firstName: 'Giulia',
            lastName: 'Rossi',
            email: 'giulia.rossi@gmail.com',
            phone: null,
            country: 'Italia',
            gift: false,
            paymentMethod: null,
            capture: null,
            items: $this->cart()->items(),
            totalCents: $this->cart()->total(),
            paymentMode: OrderPaymentMode::OnSite,
        ));
    }

    public function test_on_site_data_flagged_as_gift_is_refused(): void
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->offlineSeller()->id, 'price_cents' => 10000]));

        try {
            app(PlaceOrderAction::class)->execute(new PlaceOrderData(
                firstName: 'Giulia',
                lastName: 'Rossi',
                email: 'giulia.rossi@gmail.com',
                phone: null,
                country: 'Italia',
                gift: true,
                paymentMethod: null,
                capture: null,
                items: $this->cart()->items(),
                totalCents: $this->cart()->total(),
                paymentMode: OrderPaymentMode::OnSite,
                checkoutToken: self::TOKEN,
            ));
            $this->fail('Attesa InvalidArgumentException: in struttura non si regala.');
        } catch (InvalidArgumentException) {
            // atteso
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(1, $this->cart()->items());
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    public function test_on_site_data_carrying_gift_lines_is_refused(): void
    {
        // onSite() dice gift=false, ma le righe sono del flusso regalo.
        $this->addGiftSmartboxLine(SmartboxPackage::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 21500]));

        try {
            $this->placeOnSiteOrder(
                checkoutToken: self::TOKEN,
                itemsOverride: $this->cart()->items(true),
                totalCentsOverride: $this->cart()->total(true),
            );
            $this->fail('Attesa InvalidArgumentException: righe regalo in una prenotazione in struttura.');
        } catch (InvalidArgumentException) {
            // atteso
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(1, $this->cart()->items(true));
        Events::assertNotDispatched(OnSiteOrderConfirmed::class);
    }

    private function offlineEvent(): Event
    {
        return Event::factory()->create([
            'user_id' => $this->offlineSeller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
    }
}
