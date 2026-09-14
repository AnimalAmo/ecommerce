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
use Stripe\Exception\ApiConnectionException;
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

    public function test_un_errore_stripe_lascia_la_riga_riprovabile(): void
    {
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Insufficient funds in the Stripe account.'));

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());

        $row = $rows[0]->fresh();

        // Saldo insufficiente è il caso reale (un rimborso partito prima) ed è
        // transitorio: la riga resta riprovabile e l'errore resta visibile.
        // Chiuderla al primo tentativo significherebbe non pagare mai più quel
        // partner, in silenzio.
        $this->assertSame(PayoutStatus::Pending, $row->status);
        $this->assertSame(1, $row->payout_attempts);
        $this->assertNotNull($row->failed_at);
        $this->assertStringContainsString('Insufficient funds', $row->last_error);
    }

    public function test_esauriti_i_tentativi_la_riga_diventa_definitiva(): void
    {
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);
        $rows[0]->update(['payout_attempts' => config('commerce.payout.max_release_attempts') - 1]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Insufficient funds in the Stripe account.'));

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());

        $this->assertSame(PayoutStatus::Failed, $rows[0]->fresh()->status);
    }

    public function test_una_riga_esaurita_non_viene_piu_ritentata_dallo_scheduler(): void
    {
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);
        $rows[0]->update(['status' => PayoutStatus::Failed]);

        $this->payouts->shouldReceive('create')->never();

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());
    }

    public function test_il_comando_di_retry_rimette_in_coda_le_righe_definitive(): void
    {
        // La via d'uscita umana: dopo aver sistemato la causa (saldo, account
        // ripristinato), le righe chiuse tornano bonificabili al giro dopo.
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);
        $rows[0]->update([
            'status' => PayoutStatus::Failed,
            'payout_attempts' => 5,
            'last_error' => 'Insufficient funds in the Stripe account.',
        ]);

        $this->artisan('payouts:retry')->assertSuccessful();

        $row = $rows[0]->fresh();

        $this->assertSame(PayoutStatus::Pending, $row->status);
        $this->assertSame(0, $row->payout_attempts);
        $this->assertNull($row->failed_at);
    }

    public function test_il_ritentativo_riusa_la_chiave_del_primo_tentativo(): void
    {
        // Senza una chiave persistita, una riga che matura nel frattempo cambia
        // il gruppo, quindi la chiave, quindi Stripe vede una richiesta mai
        // vista: un secondo bonifico che ripaga anche le righe del primo.
        $partner = $this->payablePartner();
        $primo = $this->maturedRowsFor($partner, [5000]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Insufficient funds in the Stripe account.'));

        app(ReleaseMaturedPayouts::class)->run();

        $chiave = $primo[0]->fresh()->payout_idempotency_key;
        $this->assertNotNull($chiave, 'la chiave va scritta prima della chiamata');

        // Giro successivo: per lo stesso partner è maturata un'altra riga.
        $seconda = $this->maturedRowsFor($partner, [3000]);

        $chiamate = [];
        $this->payouts->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function (array $params, array $options) use (&$chiamate): Payout {
                $chiamate[] = ['amount' => $params['amount'], 'key' => $options['idempotency_key']];

                return Payout::constructFrom(['id' => 'po_'.count($chiamate)]);
            });

        app(ReleaseMaturedPayouts::class)->run();

        $vecchio = collect($chiamate)->firstWhere('amount', 5000);
        $nuovo = collect($chiamate)->firstWhere('amount', 3000);

        $this->assertNotNull($vecchio, 'il gruppo già tentato va ribonificato per conto suo');
        $this->assertSame($chiave, $vecchio['key'], 'il ritentativo deve riusare la chiave del primo tentativo');
        $this->assertNotNull($nuovo, 'la riga nuova è un bonifico a sé');
        $this->assertNotSame($chiave, $nuovo['key'], 'la riga nuova non può ereditare la chiave del gruppo vecchio');
    }

    public function test_un_esito_ignoto_non_viene_ritentato_da_solo(): void
    {
        // Un errore di connessione non dice "non è successo": dice "non so".
        // Il bonifico può essere stato creato e la risposta persa: ritentare
        // alla cieca significherebbe pagarlo due volte.
        $rows = $this->maturedRowsFor($this->payablePartner(), [4000]);

        $this->payouts->shouldReceive('create')
            ->once()
            ->andThrow(ApiConnectionException::factory('Connection timed out'));

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());

        $row = $rows[0]->fresh();

        $this->assertSame(PayoutStatus::Failed, $row->status);
        $this->assertStringContainsString('ESITO IGNOTO', (string) $row->last_error);

        // E il giro dopo non ci riprova: la riga non è più Pending.
        app(ReleaseMaturedPayouts::class)->run();
    }

    public function test_una_riga_col_netto_non_ancora_riconciliato_non_viene_bonificata(): void
    {
        // Il netto provvisorio è lordo meno provvigione: qualche centesimo più
        // di quanto il saldo contiene. Bonificarlo significa prendere
        // balance_insufficient e mettere la riga in coda ai ritentativi per
        // niente. Aspetta il giro di payouts:reconcile-net delle 05:45.
        $rows = $this->maturedRowsFor($this->payablePartner(), [5000]);
        $rows[0]->update(['net_reconciled_at' => null]);

        $this->payouts->shouldReceive('create')->never();

        $this->assertSame(0, app(ReleaseMaturedPayouts::class)->run());
        $this->assertSame(PayoutStatus::Pending, $rows[0]->fresh()->status);
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
