<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayment\OrderPayment;
use App\Models\PaymentGateway\PaymentGateway;
use App\Models\Structure\Structure;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    // ── order_number (creating hook) ────────────────────────────────────────

    public function test_order_number_is_generated_on_creating_when_empty(): void
    {
        $order = Order::factory()->create();

        $this->assertSame('ORD-000001', $order->order_number);
    }

    public function test_explicit_order_number_is_preserved(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-CUSTOM']);

        $this->assertSame('ORD-CUSTOM', $order->order_number);
    }

    // ── Relazioni ───────────────────────────────────────────────────────────

    public function test_order_relationships(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $items = OrderItem::factory()->count(2)->for($order)->create();
        $payment = OrderPayment::factory()->for($order)->completed()->create();

        $this->assertTrue($order->user->is($user));
        $this->assertSame(2, $order->items()->count());
        $this->assertTrue($order->items->first()->is($items->first()));
        $this->assertTrue($order->payment->is($payment));
        $this->assertTrue($items->first()->order->is($order));
        $this->assertTrue($payment->order->is($order));
    }

    public function test_guest_order_has_no_user(): void
    {
        $order = Order::factory()->guest()->create();

        $this->assertNull($order->user_id);
        $this->assertNull($order->user);
    }

    public function test_order_item_purchasable_morph_and_nullability(): void
    {
        $structure = Structure::factory()->create();
        $item = OrderItem::factory()->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
        ]);

        $this->assertTrue($item->purchasable->is($structure));

        // Snapshot autonomo: la riga sopravvive senza il prodotto a catalogo.
        $orphan = OrderItem::factory()->create([
            'purchasable_type' => null,
            'purchasable_id' => null,
        ]);

        $this->assertNull($orphan->purchasable);
        $this->assertNotSame('', $orphan->title);
    }

    // ── Casts (soldi SEMPRE int cents) ──────────────────────────────────────

    public function test_order_casts(): void
    {
        $order = Order::factory()->paid()->gift()->create(['total_cents' => 47600]);
        $order->refresh();

        $this->assertSame(47600, $order->total_cents);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertTrue($order->is_gift);
    }

    public function test_order_item_casts(): void
    {
        $item = OrderItem::factory()->create(['price_cents' => 21500]);
        $item->refresh();

        $this->assertSame(21500, $item->price_cents);
        $this->assertSame(ProductType::Structure, $item->product_type);
        $this->assertFalse($item->is_gift);
        $this->assertIsArray($item->options);
        $this->assertArrayHasKey('check_in', $item->options);
        $this->assertInstanceOf(Carbon::class, $item->booked_from);
        $this->assertInstanceOf(Carbon::class, $item->booked_until);
    }

    public function test_order_payment_casts(): void
    {
        $payment = OrderPayment::factory()->completed()->create(['amount_cents' => 47600]);
        $payment->refresh();

        $this->assertSame(47600, $payment->amount_cents);
        $this->assertSame(PaymentMethod::Card, $payment->payment_method);
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertIsArray($payment->provider_response);
        $this->assertInstanceOf(Carbon::class, $payment->paid_at);

        // Invariante di dominio: un pagamento Completed ha sempre un transaction_id.
        $this->assertNotNull($payment->transaction_id);
    }

    // ── PaymentGateway (seeder + scope) ─────────────────────────────────────

    public function test_payment_gateway_seeder_is_idempotent_and_scopes_work(): void
    {
        $this->seed(PaymentGatewaySeeder::class);
        $this->seed(PaymentGatewaySeeder::class);

        $this->assertSame(1, PaymentGateway::count());
        $this->assertSame(['stripe'], PaymentGateway::enabled()->ordered()->pluck('code')->all());

        // Il seeder rimuove anche l'eventuale riga paypal legacy.
        PaymentGateway::query()->create(['code' => 'paypal', 'name' => 'PayPal', 'is_enabled' => true, 'sort_order' => 1]);
        $this->seed(PaymentGatewaySeeder::class);
        $this->assertSame(['stripe'], PaymentGateway::pluck('code')->all());

        PaymentGateway::where('code', 'stripe')->update(['is_enabled' => false]);
        $this->assertSame([], PaymentGateway::enabled()->ordered()->pluck('code')->all());
    }
}
