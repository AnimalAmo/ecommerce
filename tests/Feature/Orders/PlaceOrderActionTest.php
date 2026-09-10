<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\TestCase;

/**
 * Pipeline ordine capture-first: happy path per famiglia (snapshot righe +
 * finestra prenotata + consumo capienza eventi), rollback totale sul sold-out
 * concorrente, ClearCart per flusso, order_number sequenziale e guard
 * totale-vs-capture. Orologio fisso come AvailabilityTest.
 */
class PlaceOrderActionTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        // Le mail post-incasso hanno il loro test (OrderPaidMailTest).
        Mail::fake();

        $this->buyer = User::factory()->create();
        $this->actingAs($this->buyer);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Happy path per famiglia ──────────────────────────────────────────────

    public function test_structure_order_snapshots_lines_payment_and_buyer(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);
        $this->addStructureLine($structure); // 5 notti = 500 €

        $order = $this->placeOrder();

        // Testata: buyer snapshot + stato Paid nella stessa transaction.
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('ORD-000001', $order->order_number);
        $this->assertTrue($order->user->is($this->buyer));
        $this->assertFalse($order->is_gift);
        $this->assertSame('Giulia', $order->first_name);
        $this->assertSame('Rossi', $order->last_name);
        $this->assertSame('giulia.rossi@gmail.com', $order->email);
        $this->assertSame('+393405738920', $order->phone);
        $this->assertSame('Italia', $order->country);
        $this->assertSame(50000, $order->total_cents);

        // Riga: snapshot display autonomo + morph verso il catalogo.
        $item = $order->items()->sole();
        $this->assertTrue($item->purchasable->is($structure));
        $this->assertSame($structure->name, $item->title);
        $this->assertSame(ProductType::Structure, $item->product_type);
        $this->assertSame($structure->location, $item->location);
        $this->assertStringContainsString($structure->img, $item->photo_url);
        $this->assertSame(50000, $item->price_cents);
        $this->assertFalse($item->is_gift);
        $this->assertSame(['cane' => 1], $item->options['animals']);
        $this->assertSame('2026-08-01 00:00:00', $item->booked_from->toDateTimeString());
        $this->assertSame('2026-08-06 00:00:00', $item->booked_until->toDateTimeString());

        // Pagamento: Completed dal capture result, importo = totale.
        $payment = $order->payment;
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame(PaymentMethod::Card, $payment->payment_method);
        $this->assertSame(50000, $payment->amount_cents);
        $this->assertSame('pi_test_1', $payment->transaction_id);
        $this->assertSame('pi_test_1', $payment->gateway_session_id);
        $this->assertSame('stripe', $payment->provider);
        $this->assertSame(['status' => 'succeeded'], $payment->provider_response);
        $this->assertNotNull($payment->paid_at);

        // Carrello svuotato del flusso ordinato.
        $this->assertTrue($this->cart()->items()->isEmpty());
    }

    public function test_service_order_books_the_whole_day(): void
    {
        $service = Structure::factory()->service()->create(['price_cents' => 3000]);
        $this->cart()->addItem('structure', $service->id, [
            'day' => '2026-08-01',
            'time_from' => '10:00',
            'time_to' => '16:00',
            'animals' => ['cane' => 1],
        ], false);

        $order = $this->placeOrder();

        $item = $order->items()->sole();
        $this->assertSame(ProductType::Service, $item->product_type);
        $this->assertSame(18000, $item->price_cents); // 6 ore × 30 €
        $this->assertSame('2026-08-01 00:00:00', $item->booked_from->toDateTimeString());
        $this->assertSame('2026-08-01 23:59:59', $item->booked_until->toDateTimeString());
    }

    public function test_event_order_books_real_dates_and_increments_seats(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        $order = $this->placeOrder();

        $item = $order->items()->sole();
        $this->assertSame(ProductType::Event, $item->product_type);
        $this->assertSame(5000, $item->price_cents);
        $this->assertSame('2026-08-10 18:00:00', $item->booked_from->toDateTimeString());
        $this->assertSame('2026-08-10 20:00:00', $item->booked_until->toDateTimeString());

        // Capienza consumata sotto lock nella pipeline.
        $this->assertSame(2, $event->refresh()->booked_participants);
    }

    public function test_activity_order_counts_guests_and_reserves_seats(): void
    {
        $activity = Event::factory()->activity(3)->create([
            'price_cents' => 11800,
            'max_participants' => 20,
        ]);
        $this->cart()->addItem('event', $activity->id, [
            'guests' => ['adulti' => 2, 'ragazzi' => 1, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false);

        $order = $this->placeOrder();

        $item = $order->items()->sole();
        $this->assertSame(ProductType::Activity, $item->product_type);
        $this->assertSame(35400, $item->price_cents); // 3 persone × 118 €
        // Attività senza data puntuale: nessuna finestra prenotata.
        $this->assertNull($item->booked_from);
        $this->assertNull($item->booked_until);

        // Persone = somma ospiti (stesso helper del pricing).
        $this->assertSame(3, $activity->refresh()->booked_participants);
    }

    public function test_smartbox_gift_order_snapshots_gift_options_and_validity(): void
    {
        $box = SmartboxPackage::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 21500, 'validity_months' => 12]);
        $this->addGiftSmartboxLine($box);

        $order = $this->placeOrder(gift: true);

        $this->assertTrue($order->is_gift);
        $this->assertSame(21500, $order->total_cents);

        $item = $order->items()->sole();
        $this->assertTrue($item->is_gift);
        $this->assertSame($box->title, $item->title);
        // La smartbox non ha località: snapshot dell'audience (come la card carrello).
        $this->assertSame($box->audience, $item->location);
        $this->assertSame([
            'dedication' => 'Marco',
            'message' => 'Tanti auguri!',
            'recipient_email' => 'marco@example.com',
        ], $item->options['gift']);
        // Validità del cofanetto: oggi → oggi + validity_months.
        $this->assertSame('2026-07-15 12:00:00', $item->booked_from->toDateTimeString());
        $this->assertSame('2027-07-15 12:00:00', $item->booked_until->toDateTimeString());
    }

    public function test_guest_order_has_no_user(): void
    {
        Auth::logout(); // carrello di sessione, come un vero guest

        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);
        $this->addStructureLine($structure);

        $order = $this->placeOrder();

        $this->assertNull($order->user_id);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertTrue($this->cart()->items()->isEmpty());
    }

    // ── Sold-out concorrente: rollback totale ────────────────────────────────

    public function test_sold_out_line_rolls_back_the_whole_order(): void
    {
        $available = Event::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $soldOut = Event::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-11 18:00:00',
            'ends_at' => '2026-08-11 20:00:00',
        ]);
        $this->cart()->addItem('event', $available->id, ['participants' => 2], false);
        $this->cart()->addItem('event', $soldOut->id, ['participants' => 2], false);

        // Acquirenti concorrenti DOPO l'aggiunta al carrello: restano 1 < 2 posti.
        $soldOut->update(['booked_participants' => 9]);

        try {
            $this->placeOrder();
            $this->fail('Attesa CartValidationException sold-out.');
        } catch (CartValidationException $exception) {
            $this->assertSame('Non ci sono abbastanza posti disponibili.', $exception->getMessage());
        }

        // Rollback TOTALE: niente ordine/righe/pagamento a db.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_payments', 0);

        // Anche l'increment della prima riga è rollbackato; il counter altrui resta.
        $this->assertSame(0, $available->refresh()->booked_participants);
        $this->assertSame(9, $soldOut->refresh()->booked_participants);

        // Il carrello non viene toccato: l'utente può correggere e riprovare.
        $this->assertCount(2, $this->cart()->items());
        Mail::assertNothingSent();
    }

    // ── Prodotto cancellato fra snapshot e pipeline: mai un 500 ──────────────

    public function test_deleted_purchasable_fails_as_cart_validation_not_model_not_found(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);
        $this->addStructureLine($structure);

        // Snapshot righe PRIMA della cancellazione (come handlePaymentCallback):
        // il purchasable sparisce nella finestra fra lettura e pipeline.
        $items = $this->cart()->items();
        $totalCents = $this->cart()->total();
        $structure->delete();

        try {
            $this->placeOrder(itemsOverride: $items, totalCentsOverride: $totalCents);
            $this->fail('Attesa CartValidationException per purchasable cancellato.');
        } catch (CartValidationException $exception) {
            // CartValidationException (non ModelNotFound): il chiamante storna e resta allo step 2.
            $this->assertSame(__('cart.not_purchasable'), $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_deleted_smartbox_fails_as_cart_validation_not_model_not_found(): void
    {
        $box = SmartboxPackage::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 21500]);
        $this->addGiftSmartboxLine($box);

        $items = $this->cart()->items(true);
        $totalCents = $this->cart()->total(true);
        $box->delete();

        $this->expectException(CartValidationException::class);

        $this->placeOrder(gift: true, itemsOverride: $items, totalCentsOverride: $totalCents);
    }

    // ── ClearCart: solo il flusso ordinato ───────────────────────────────────

    public function test_ordering_the_gift_flow_leaves_the_normal_lines_in_the_cart(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);
        $normalKey = $this->addStructureLine($structure);
        $this->addGiftSmartboxLine(SmartboxPackage::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 21500]));

        $this->placeOrder(gift: true);

        // Il flusso regalo è stato svuotato, la riga normale è intatta.
        $this->assertTrue($this->cart()->items(true)->isEmpty());
        $remaining = $this->cart()->items();
        $this->assertCount(1, $remaining);
        $this->assertSame($normalKey, $remaining->first()->key);
        $this->assertFalse($remaining->first()->isGift);
    }

    // ── order_number sequenziale ─────────────────────────────────────────────

    public function test_order_numbers_are_sequential(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);

        $this->addStructureLine($structure);
        $first = $this->placeOrder();

        // Capture DIVERSO dal primo: lo stesso gateway_session_id sarebbe un
        // replay e verrebbe (giustamente) rifiutato dal guard idempotente.
        $this->addStructureLine($structure);
        $second = $this->placeOrder(gatewaySessionId: 'pi_test_2');

        $this->assertSame('ORD-000001', $first->order_number);
        $this->assertSame('ORD-000002', $second->order_number);
    }

    // ── Guard totale-vs-capture ──────────────────────────────────────────────

    public function test_total_mismatch_aborts_before_any_write(): void
    {
        $structure = Structure::factory()->create([
            'user_id' => $this->seller()->id, 'price_cents' => 10000]);
        $this->addStructureLine($structure);

        try {
            // Capture riuscito per un importo diverso dalla somma righe: mai un ordine.
            $this->placeOrder(totalCentsOverride: $this->cart()->total() + 100);
            $this->fail('Atteso RuntimeException per totale disallineato.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('mismatch', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertCount(1, $this->cart()->items());
    }
}
