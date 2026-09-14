<?php

namespace Tests\Unit\Payment;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Payment\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\InvalidRequestException;
use Stripe\Service\AccountLinkService;
use Stripe\Service\AccountService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Onboarding Connect. Account Standard: la documentazione Stripe raccomanda i
 * direct charges proprio per gli account con dashboard completa, e con
 * Standard dispute e saldo negativo restano in capo al partner — cioè la
 * decisione della cliente. Nessun controller.fees.payer: con i direct charges
 * le commissioni Stripe sono già a carico dell'account connesso.
 */
class StripeConnectServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $accounts;

    private MockInterface $accountLinks;

    private StripeConnectService $connect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accounts = Mockery::mock(AccountService::class);
        $this->accountLinks = Mockery::mock(AccountLinkService::class);

        // StripeClient::__get delega a getService(): basta stubbare quello.
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('accounts')->andReturn($this->accounts);
        $client->shouldReceive('getService')->with('accountLinks')->andReturn($this->accountLinks);

        $this->connect = new StripeConnectService($client);
    }

    public function test_crea_un_account_standard_italiano_e_lo_memorizza(): void
    {
        $partner = $this->partner();

        $this->accounts->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $params): bool => $params['type'] === 'standard'
                && $params['country'] === 'IT'
                && $params['email'] === $partner->email
                && $params['metadata']['partner_user_id'] === (string) $partner->id))
            ->andReturn(Account::constructFrom(['id' => 'acct_created']));

        $this->assertSame('acct_created', $this->connect->ensureAccountFor($partner));
        $this->assertSame('acct_created', $partner->partnerProfile->fresh()->stripe_account_id);
    }

    public function test_un_account_gia_creato_non_viene_ricreato(): void
    {
        $partner = $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('create')->never();

        $this->assertSame('acct_existing', $this->connect->ensureAccountFor($partner));
    }

    public function test_il_link_di_onboarding_punta_all_account_del_partner(): void
    {
        $partner = $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accountLinks->shouldReceive('create')
            ->once()
            ->with([
                'account' => 'acct_existing',
                'type' => 'account_onboarding',
                'return_url' => 'https://animalamo.test/ritorno',
                'refresh_url' => 'https://animalamo.test/riprova',
            ])
            ->andReturn(AccountLink::constructFrom(['url' => 'https://connect.stripe.com/setup/x']));

        $url = $this->connect->onboardingUrl($partner, 'https://animalamo.test/ritorno', 'https://animalamo.test/riprova');

        $this->assertSame('https://connect.stripe.com/setup/x', $url);
    }

    public function test_sync_rispecchia_lo_stato_dichiarato_da_stripe(): void
    {
        $partner = $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with('acct_existing')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_existing',
                'charges_enabled' => true,
                'payouts_enabled' => false,
                'requirements' => ['currently_due' => ['external_account']],
            ]));

        $this->connect->syncAccountState('acct_existing');

        $profile = $partner->partnerProfile->fresh();

        $this->assertTrue($profile->stripe_charges_enabled);
        $this->assertFalse($profile->stripe_payouts_enabled);
        $this->assertSame(['external_account'], $profile->stripe_requirements_due);
    }

    public function test_sync_mette_su_manuale_la_pianificazione_dei_bonifici(): void
    {
        // Senza questo, Stripe bonifica in automatico dopo pochi giorni e la
        // trattenuta di 14 giorni prevista dalle Condizioni Fornitore non
        // esiste: payouts:release troverebbe il saldo gia' vuoto.
        $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with('acct_existing')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_existing',
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'requirements' => ['currently_due' => []],
                'settings' => ['payouts' => ['schedule' => ['interval' => 'daily']]],
            ]));

        $this->accounts->shouldReceive('update')
            ->once()
            ->with('acct_existing', ['settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]]])
            ->andReturn(Account::constructFrom(['id' => 'acct_existing']));

        $this->connect->syncAccountState('acct_existing');
    }

    public function test_sync_non_tocca_una_pianificazione_gia_manuale(): void
    {
        $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with('acct_existing')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_existing',
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'requirements' => ['currently_due' => []],
                'settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]],
            ]));

        $this->accounts->shouldReceive('update')->never();

        $this->connect->syncAccountState('acct_existing');
    }

    public function test_sync_non_impone_il_manuale_finche_i_bonifici_non_sono_abilitati(): void
    {
        // Onboarding a meta': Stripe rifiuta la modifica su un account che non
        // ha ancora la capability, e l'evento successivo ripassa di qui.
        $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with('acct_existing')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_existing',
                'charges_enabled' => true,
                'payouts_enabled' => false,
                'requirements' => ['currently_due' => ['external_account']],
                'settings' => ['payouts' => ['schedule' => ['interval' => 'daily']]],
            ]));

        $this->accounts->shouldReceive('update')->never();

        $this->connect->syncAccountState('acct_existing');
    }

    public function test_un_rifiuto_sulla_pianificazione_non_fa_fallire_il_sync(): void
    {
        // L'endpoint webhook è condiviso con gli incassi: se un rifiuto di
        // Stripe sulla pianificazione risalisse al controller, questo
        // risponderebbe 400, Stripe riconsegnerebbe per giorni e finirebbe per
        // disabilitare l'endpoint — portandosi dietro payment_intent.*.
        $partner = $this->partner(['stripe_account_id' => 'acct_existing']);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with('acct_existing')
            ->andReturn(Account::constructFrom([
                'id' => 'acct_existing',
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'requirements' => ['currently_due' => []],
                'settings' => ['payouts' => ['schedule' => ['interval' => 'daily']]],
            ]));

        $this->accounts->shouldReceive('update')
            ->once()
            ->andThrow(InvalidRequestException::factory('Non puoi modificare questo account.'));

        $this->connect->syncAccountState('acct_existing');

        // Lo specchio dei flag resta comunque allineato.
        $this->assertTrue($partner->partnerProfile->fresh()->stripe_payouts_enabled);
    }

    public function test_sync_di_un_account_sconosciuto_non_esplode(): void
    {
        // Account non nostro (o profilo cancellato): l'evento si ignora senza
        // nemmeno chiedere a Stripe chi sia.
        $this->accounts->shouldReceive('retrieve')->never();

        $this->connect->syncAccountState('acct_ignoto');

        $this->assertDatabaseCount('partner_profiles', 0);
    }

    private function partner(array $profile = []): User
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create($profile);

        return $partner->fresh();
    }
}
