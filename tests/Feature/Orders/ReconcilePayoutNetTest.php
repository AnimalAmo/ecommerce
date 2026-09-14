<?php

namespace Tests\Feature\Orders;

use App\Enums\PayoutStatus;
use App\Models\Order\Order;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\TestCase;

/**
 * Al capture la balance transaction non esiste ancora — l'addebito c'è, il suo
 * `balance_transaction` è null — quindi il registro nasce con lordo meno
 * provvigione, che è qualche centesimo più di quanto il saldo del partner
 * conterrà. Questa riconciliazione rilegge il netto vero quando Stripe l'ha
 * calcolato, prima che il bonifico parta.
 */
class ReconcilePayoutNetTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    private MockInterface $paymentIntents;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        $this->actingAs(User::factory()->create());

        $this->paymentIntents = Mockery::mock(PaymentIntentService::class);
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($this->paymentIntents);
        $this->app->instance(StripeClient::class, $client);
    }

    public function test_il_netto_provvisorio_viene_sostituito_da_quello_accreditato(): void
    {
        $order = $this->orderWithProvisionalNet(12000);
        $payout = $order->payouts()->sole();

        $this->assertSame(10800, $payout->net_cents, 'parte dal netto provvisorio');

        $this->stripeReturnsNet('pi_recon', 10595);

        $this->artisan('payouts:reconcile-net')->assertSuccessful();

        $payout->refresh();

        $this->assertSame(10595, $payout->net_cents);
        $this->assertNotNull($payout->net_reconciled_at);
    }

    public function test_una_riga_gia_riconciliata_non_richiama_stripe(): void
    {
        $order = $this->orderWithProvisionalNet(12000);
        $order->payouts()->update(['net_reconciled_at' => now()]);

        $this->paymentIntents->shouldReceive('retrieve')->never();

        $this->artisan('payouts:reconcile-net')->assertSuccessful();
    }

    public function test_se_stripe_non_ha_ancora_il_netto_la_riga_resta_da_riconciliare(): void
    {
        $order = $this->orderWithProvisionalNet(12000);

        $this->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_recon',
                'latest_charge' => ['id' => 'ch_1', 'balance_transaction' => null],
            ]));

        $this->artisan('payouts:reconcile-net')->assertSuccessful();

        $payout = $order->payouts()->sole();

        $this->assertNull($payout->net_reconciled_at, 'niente data: si riproverà');
        $this->assertSame(10800, $payout->net_cents, 'e il netto provvisorio non si tocca');
    }

    // ── helper ──────────────────────────────────────────────────────────

    private function orderWithProvisionalNet(int $totalCents): Order
    {
        $this->addStructureLine($this->structureAt($totalCents));

        $order = $this->placeOrder(gatewaySessionId: 'pi_recon');

        // Come nasce davvero: netto provvisorio, nessuna riconciliazione.
        $order->payouts()->update([
            'net_reconciled_at' => null,
            'status' => PayoutStatus::Pending,
        ]);

        return $order;
    }

    private function structureAt(int $totalCents): Structure
    {
        return Structure::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => intdiv($totalCents, 5),
            'animal_supplement_cents' => 0,
        ]);
    }

    private function stripeReturnsNet(string $intentId, int $net): void
    {
        $intent = PaymentIntent::constructFrom(['id' => $intentId]);
        $intent->latest_charge = StripeObject::constructFrom([
            'id' => 'ch_1',
            'balance_transaction' => ['id' => 'txn_1', 'net' => $net],
        ]);

        $this->paymentIntents->shouldReceive('retrieve')->once()->andReturn($intent);
    }
}
