<?php

namespace Tests\Feature\Orders;

use App\Enums\PayoutStatus;
use App\Models\OrderPayout\OrderPayout;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Payout\ReleaseMaturedPayouts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Exception\InvalidRequestException;
use Stripe\Payout;
use Stripe\Service\PayoutService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Con i direct charges non si trasferisce un ordine: si emette UN payout di un
 * importo dal saldo di un account. Più righe mature dello stesso partner
 * finiscono quindi in un solo payout e ne condividono l'id.
 */
class ReleaseMaturedPayoutsTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $payouts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payouts = Mockery::mock(PayoutService::class);

        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('payouts')->andReturn($this->payouts);

        $this->app->instance(StripeClient::class, $client);
    }

    public function test_le_righe_mature_dello_stesso_partner_fanno_un_solo_payout(): void
    {
        $rows = $this->maturedRowsFor($this->payablePartner(), [5000, 3000]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->with(['amount' => 8000, 'currency' => 'eur'], Mockery::on(
                fn (array $options): bool => $options['stripe_account'] === 'acct_payable'
                    && isset($options['idempotency_key']),
            ))
            ->andReturn(Payout::constructFrom(['id' => 'po_1']));

        $this->assertSame(1, app(ReleaseMaturedPayouts::class)->run());

        foreach ($rows as $row) {
            $row->refresh();
            $this->assertSame(PayoutStatus::Released, $row->status);
            $this->assertSame('po_1', $row->stripe_payout_id);
            $this->assertNotNull($row->released_at);
        }
    }

    public function test_le_righe_non_mature_restano_ferme(): void
    {
        $row = OrderPayout::factory()->create([
            'partner_user_id' => $this->payablePartner()->id,
            'stripe_account_id' => 'acct_payable',
            'release_at' => now()->addDay(),
            'status' => PayoutStatus::Pending,
        ]);

        $this->payouts->shouldReceive('create')->never();

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());
        $this->assertSame(PayoutStatus::Pending, $row->fresh()->status);
    }

    public function test_un_partner_non_ancora_bonificabile_viene_saltato(): void
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create([
            'stripe_account_id' => 'acct_half',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => false,
        ]);
        $row = $this->maturedRowsFor($partner, [4000], 'acct_half')[0];

        // Payout bloccati su Stripe finché l'onboarding non è completo: meglio
        // non chiedere che incassare un errore.
        $this->payouts->shouldReceive('create')->never();

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());
        $this->assertSame(PayoutStatus::Pending, $row->fresh()->status);
    }

    public function test_un_errore_stripe_lascia_le_righe_in_stato_failed(): void
    {
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Insufficient funds in the Stripe account.'));

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());

        $row = $rows[0]->fresh();

        // Saldo insufficiente è il caso reale (rimborso partito prima): deve
        // restare visibile, non finire in un catch muto.
        $this->assertSame(PayoutStatus::Failed, $row->status);
        $this->assertNotNull($row->failed_at);
        $this->assertStringContainsString('Insufficient funds', $row->last_error);
    }

    public function test_le_righe_di_sola_piattaforma_non_vengono_mai_bonificate(): void
    {
        $row = OrderPayout::factory()->create([
            'partner_user_id' => null,
            'stripe_account_id' => null,
            'release_at' => now()->subDay(),
            'status' => PayoutStatus::PlatformOnly,
        ]);

        $this->payouts->shouldReceive('create')->never();

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());
        $this->assertSame(PayoutStatus::PlatformOnly, $row->fresh()->status);
    }

    private function payablePartner(): User
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->connected()->for($partner)->create(['stripe_account_id' => 'acct_payable']);

        return $partner;
    }

    /** @return list<OrderPayout> */
    private function maturedRowsFor(User $partner, array $netCents, string $account = 'acct_payable'): array
    {
        return array_map(fn (int $net): OrderPayout => OrderPayout::factory()->matured()->create([
            'partner_user_id' => $partner->id,
            'stripe_account_id' => $account,
            'gross_cents' => $net,
            'commission_cents' => 0,
            'net_cents' => $net,
        ]), $netCents);
    }
}
