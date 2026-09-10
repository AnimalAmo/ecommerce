<?php

namespace Tests\Feature\Orders;

use App\Enums\PayoutStatus;
use App\Models\Order\Order;
use App\Models\OrderPayout\OrderPayout;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\TestCase;

/**
 * Il registro dei rilasci: una riga per riga d'ordine, e la somma dei lordi
 * deve fare esattamente il totale dell'ordine. È l'invariante che rende
 * verificabile tutto il resto.
 */
class OrderPayoutsTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        $this->actingAs(User::factory()->create());
    }

    public function test_ogni_riga_ordine_genera_una_riga_di_payout(): void
    {
        $order = $this->placeOrderWithTotalCents(12000);

        $this->assertSame(
            $order->items()->count(),
            OrderPayout::where('order_id', $order->id)->count(),
        );
    }

    public function test_la_somma_dei_lordi_e_il_totale_ordine(): void
    {
        $order = $this->placeOrderWithTotalCents(12000);

        $this->assertSame(
            $order->total_cents,
            (int) OrderPayout::where('order_id', $order->id)->sum('gross_cents'),
        );
    }

    public function test_sopra_soglia_la_provvigione_e_il_dieci_percento(): void
    {
        $order = $this->placeOrderWithTotalCents(12000);
        $payout = OrderPayout::where('order_id', $order->id)->sole();

        $this->assertSame(1200, $payout->commission_cents);
        $this->assertSame(10800, $payout->net_cents);
        $this->assertSame(PayoutStatus::Pending, $payout->status);
        $this->assertSame($this->seller()->id, $payout->partner_user_id);
    }

    public function test_sotto_soglia_la_provvigione_e_zero_e_il_netto_e_il_lordo(): void
    {
        $order = $this->placeOrderWithTotalCents(4000);
        $payout = OrderPayout::where('order_id', $order->id)->sole();

        $this->assertSame(0, $payout->commission_cents);
        $this->assertSame(4000, $payout->net_cents);
    }

    public function test_il_rilascio_non_e_mai_prima_dei_giorni_di_recesso(): void
    {
        $order = $this->placeOrderWithTotalCents(12000);
        $payout = OrderPayout::where('order_id', $order->id)->sole();

        $this->assertTrue(
            $payout->release_at->greaterThanOrEqualTo(now()->addDays(14)->startOfDay()),
            'Il rilascio è stato programmato prima della scadenza del recesso.',
        );
    }

    public function test_su_piu_righe_la_provvigione_si_ripartisce_senza_perdere_centesimi(): void
    {
        // 12005 cents in due righe: il 10% è 1200, che non si divide in
        // proporzione senza resto. Il resto va all'ultima riga.
        $this->addStructureLine($this->structureAt(6000));
        $this->addStructureLine($this->structureAt(6005));

        $order = $this->placeOrder();
        $payouts = OrderPayout::where('order_id', $order->id)->get();

        $this->assertCount(2, $payouts);
        $this->assertSame(12005, $order->total_cents);
        $this->assertSame(1200, (int) $payouts->sum('commission_cents'));
        $this->assertSame(10805, (int) $payouts->sum('net_cents'));
        $this->assertSame($order->total_cents, (int) $payouts->sum('gross_cents'));
    }

    public function test_la_factory_produce_una_riga_coerente_col_suo_ordine(): void
    {
        $payout = OrderPayout::factory()->matured()->create();

        $this->assertSame($payout->orderItem->order_id, $payout->order_id);
        $this->assertSame($payout->gross_cents - $payout->commission_cents, $payout->net_cents);
        $this->assertTrue($payout->release_at->isPast());
        $this->assertInstanceOf(User::class, $payout->partner);
    }

    /** Struttura del venditore unico, prezzata perché 5 notti facciano $totalCents. */
    private function structureAt(int $totalCents): Structure
    {
        return Structure::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => intdiv($totalCents, 5),
            'animal_supplement_cents' => 0,
        ]);
    }

    /** Cinque notti di una struttura senza supplemento animali: totale = 5 × prezzo. */
    private function placeOrderWithTotalCents(int $totalCents): Order
    {
        $this->addStructureLine($this->structureAt($totalCents));

        return $this->placeOrder();
    }
}
