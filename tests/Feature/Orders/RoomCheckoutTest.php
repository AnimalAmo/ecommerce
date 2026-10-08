<?php

namespace Tests\Feature\Orders;

use App\Exceptions\CartValidationException;
use App\Livewire\Commerce\Checkout;
use App\Livewire\Partner\Bookings\PartnerBookingDetail;
use App\Livewire\Profile\ProfileOrderSummary;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Stanza al checkout: lock della riga rooms e ricontrollo dell'occupazione
 * nella transaction dell'ordine (partner Online), snapshot room_id/room_name
 * sulla riga ordine, storno quando la stanza è piena o sparita dopo il
 * capture, righe dello stesso carrello sulla stessa stanza contate fra loro.
 * Orologio fisso come PlaceOrderActionTest.
 */
class RoomCheckoutTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        Mail::fake();

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

    // ── Snapshot sulla riga ordine ───────────────────────────────────────────

    public function test_order_item_stores_room_id_and_name(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->seller());
        $this->addRoomLine($room);

        $item = $this->placeOrder()->items()->sole();

        $this->assertSame($room->id, $item->room_id);
        $this->assertSame('Camera Vista Mare', $item->options['room_name']);
        $this->assertSame($room->id, (int) $item->options['room_id']);
    }

    // ── Stanza piena: il secondo checkout perde ──────────────────────────────

    public function test_second_checkout_on_full_room_fails_and_refunds(): void
    {
        $room = $this->roomOf($this->seller());

        // B arriva allo step 2 con la stanza ancora libera.
        $second = $this->buyer('b@example.com');
        $this->actingAs($second);
        $this->addRoomLine($room);
        $totalCents = $this->cart()->total();

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2);

        // Nel frattempo A paga l'ultima unità.
        $this->actingAs($this->buyer('a@example.com'));
        $this->addRoomLine($room);
        $this->placeOrder(gatewaySessionId: 'pi_first');

        $this->actingAs($second);
        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        // Storno pieno come nel sold-out evento.
        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => $totalCents, 'stripe_account_id' => $this->seller()->partnerProfile->stripe_account_id]],
            $this->gateway->refundCalls,
        );

        // Solo l'ordine di A esiste; il carrello di B resta per riprovare.
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertCount(1, $this->cart()->items());
    }

    public function test_second_order_on_full_room_is_a_cart_validation_error(): void
    {
        $room = $this->roomOf($this->seller());

        $second = $this->buyer('b@example.com');
        $this->actingAs($second);
        $this->addRoomLine($room);

        $this->actingAs($this->buyer('a@example.com'));
        $this->addRoomLine($room);
        $this->placeOrder(gatewaySessionId: 'pi_first');

        $this->actingAs($second);

        try {
            $this->placeOrder(gatewaySessionId: 'pi_second');
            $this->fail('Attesa CartValidationException per stanza piena.');
        } catch (CartValidationException $exception) {
            $this->assertSame(CartValidationException::unavailableDates()->getMessage(), $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_room_with_two_units_takes_two_overlapping_orders(): void
    {
        $room = $this->roomOf($this->seller(), units: 2);

        $second = $this->buyer('b@example.com');
        $this->actingAs($second);
        $this->addRoomLine($room);

        $this->actingAs($this->buyer('a@example.com'));
        $this->addRoomLine($room);
        $this->placeOrder(gatewaySessionId: 'pi_first');

        $this->actingAs($second);
        $this->placeOrder(gatewaySessionId: 'pi_second');

        $this->assertSame(2, OrderItem::query()->where('room_id', $room->id)->count());
    }

    // ── Righe dello stesso carrello sulla stessa stanza ──────────────────────

    public function test_two_overlapping_lines_for_the_same_room_in_one_cart_fail(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->seller());
        $this->addRoomLine($room, '2026-08-01', '2026-08-06');
        $this->addRoomLine($room, '2026-08-04', '2026-08-08');

        try {
            $this->placeOrder();
            $this->fail('Attesa CartValidationException: la seconda riga non trova unità libere.');
        } catch (CartValidationException $exception) {
            $this->assertSame(CartValidationException::unavailableDates()->getMessage(), $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_back_to_back_lines_for_the_same_room_in_one_cart_succeed(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->seller());
        $this->addRoomLine($room, '2026-08-01', '2026-08-06');
        // La notte del check-out è libera: chi entra il 06 non si sovrappone.
        $this->addRoomLine($room, '2026-08-06', '2026-08-08');

        $this->assertCount(2, $this->placeOrder()->items);
    }

    // ── Stanza sparita o mai scelta: errore pulito, mai un 500 ───────────────

    public function test_deleted_room_in_cart_fails_cleanly(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->seller());
        Room::factory()->for($room->structure)->create(); // la struttura ha ancora stanze
        $this->addRoomLine($room);
        $totalCents = $this->cart()->total();

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2);

        $room->delete();

        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => $totalCents, 'stripe_account_id' => $this->seller()->partnerProfile->stripe_account_id]],
            $this->gateway->refundCalls,
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_deleted_room_is_a_cart_validation_error_not_a_crash(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->seller());
        $this->addRoomLine($room);
        $room->delete();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(CartValidationException::notPurchasable()->getMessage());

        $this->placeOrder();
    }

    public function test_legacy_line_without_room_fails_once_the_structure_has_rooms(): void
    {
        $this->actingAs($this->buyer());
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000, 'animal_supplement_cents' => 0]);
        $this->addStructureLine($structure); // riga senza room_id, struttura ancora senza stanze

        Room::factory()->for($structure)->create();

        try {
            $this->placeOrder();
            $this->fail('Attesa CartValidationException per riga senza stanza.');
        } catch (CartValidationException $exception) {
            $this->assertSame(CartValidationException::notPurchasable()->getMessage(), $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
    }

    // ── Partner in struttura: niente occupazione, stanza comunque valida ─────

    public function test_onsite_partner_books_a_full_room_without_occupancy_check(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->offlineSeller());
        $this->addRoomLine($room);

        // Unica unità già presa da una prenotazione confermata sulle stesse date.
        OrderItem::factory()->create([
            'order_id' => Order::factory()->onSite()->create()->id,
            'room_id' => $room->id,
            'booked_from' => '2026-08-01',
            'booked_until' => '2026-08-06',
        ]);

        $item = $this->placeOnSiteOrder()->items()->sole();

        $this->assertSame($room->id, $item->room_id);
        $this->assertSame('Camera Vista Mare', $item->options['room_name']);
    }

    public function test_onsite_partner_deleted_room_fails_cleanly(): void
    {
        $this->actingAs($this->buyer());
        $room = $this->roomOf($this->offlineSeller());
        $this->addRoomLine($room);
        $room->delete();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(CartValidationException::notPurchasable()->getMessage());

        $this->placeOnSiteOrder();
    }

    // ── Visualizzazione ──────────────────────────────────────────────────────

    public function test_partner_bookings_show_room_name(): void
    {
        $partner = $this->actingAsActivePartner();
        $item = $this->roomBooking($partner);

        $this->get(route('partner.bookings'))
            ->assertOk()
            ->assertSee(__('orders.room', ['name' => 'Camera Vista Mare']));

        Livewire::test(PartnerBookingDetail::class, ['booking' => $item])
            ->assertSee('Camera Vista Mare');
    }

    public function test_customer_order_summary_shows_room_name(): void
    {
        $buyer = User::factory()->create();
        $item = $this->roomBooking(User::factory()->create(), ['user_id' => $buyer->id]);

        Livewire::actingAs($buyer)
            ->test(ProfileOrderSummary::class, ['order' => $item->order->order_number])
            ->assertSee(__('orders.room', ['name' => 'Camera Vista Mare']));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buyer(string $email = 'giulia@example.com'): User
    {
        return User::factory()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => $email,
            'phone' => '340 5738920',
        ]);
    }

    /** Stanza "Camera Vista Mare" (80 €/notte) di una struttura del venditore. */
    private function roomOf(User $seller, int $units = 1): Room
    {
        $structure = Structure::factory()->create([
            'user_id' => $seller->id, 'price_cents' => 10000, 'animal_supplement_cents' => 0]);

        return Room::factory()->for($structure)->units($units)->create([
            'name' => 'Camera Vista Mare',
            'price_cents' => 8000,
        ]);
    }

    /** Riga stanza: di default 01/08 → 06/08, 2 adulti e 1 cane. */
    private function addRoomLine(Room $room, string $checkIn = '2026-08-01', string $checkOut = '2026-08-06'): int|string
    {
        return $this->cart()->addItem('structure', $room->structure_id, [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'room_id' => $room->id,
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga ordine già scritta (snapshot) su una stanza di una struttura del partner. */
    private function roomBooking(User $partner, array $orderState = []): OrderItem
    {
        $structure = Structure::factory()->create(['user_id' => $partner->id]);

        return OrderItem::factory()->create([
            'order_id' => Order::factory()->paid()->create($orderState)->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'title' => 'Hotel Bau Resort',
            'booked_from' => '2026-08-01',
            'booked_until' => '2026-08-06',
            'room_id' => null,
            'options' => ['check_in' => '2026-08-01', 'check_out' => '2026-08-06', 'room_name' => 'Camera Vista Mare'],
        ]);
    }
}
