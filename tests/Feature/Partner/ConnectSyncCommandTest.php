<?php

namespace Tests\Feature\Partner;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Payment\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\Exception\ApiConnectionException;
use Tests\TestCase;

/**
 * Riallineamento in blocco dello stato Connect. È la via d'uscita quando il
 * webhook `account.updated` non è arrivato: senza, l'unico modo di sbloccare
 * un partner sarebbe aspettare che Stripe emetta spontaneamente un altro
 * evento per quell'account.
 */
class ConnectSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_riallinea_solo_i_profili_con_un_account_stripe(): void
    {
        $this->profileWith('acct_uno');
        $this->profileWith('acct_due');
        $this->profileWith(null);

        $this->mock(StripeConnectService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncAccountState')->once()->with('acct_uno');
            $mock->shouldReceive('syncAccountState')->once()->with('acct_due');
        });

        $this->artisan('animalamo:connect-sync')->assertSuccessful();
    }

    public function test_un_account_che_fallisce_non_ferma_gli_altri(): void
    {
        $this->profileWith('acct_rotto');
        $this->profileWith('acct_sano');

        $this->mock(StripeConnectService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncAccountState')
                ->once()
                ->with('acct_rotto')
                ->andThrow(ApiConnectionException::factory('Stripe irraggiungibile'));
            $mock->shouldReceive('syncAccountState')->once()->with('acct_sano');
        });

        $this->artisan('animalamo:connect-sync')->assertSuccessful();
    }

    private function profileWith(?string $accountId): void
    {
        PartnerProfile::factory()->for(User::factory()->create())->create([
            'stripe_account_id' => $accountId,
        ]);
    }
}
