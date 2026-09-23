<?php

namespace Tests\Feature\Partner;

use App\Jobs\PublishAwaitingDrafts;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Payment\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Stripe\Account;
use Stripe\Exception\ApiConnectionException;
use Stripe\Service\AccountService;
use Stripe\StripeClient;
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

    public function test_un_riallineamento_che_rende_pagabile_mette_in_coda_la_pubblicazione(): void
    {
        // L'aggancio sta in syncAccountState: il comando lo eredita senza modifiche.
        Queue::fake();
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create(['stripe_account_id' => 'acct_pronto']);

        $accounts = Mockery::mock(AccountService::class);
        $accounts->shouldReceive('retrieve')->once()->with('acct_pronto')->andReturn(Account::constructFrom([
            'id' => 'acct_pronto',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'requirements' => ['currently_due' => []],
            'settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]],
        ]));
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('accounts')->andReturn($accounts);
        $this->app->instance(StripeConnectService::class, new StripeConnectService($client));

        $this->artisan('animalamo:connect-sync')->assertSuccessful();

        Queue::assertPushed(PublishAwaitingDrafts::class, fn (PublishAwaitingDrafts $job): bool => $job->partnerId === $partner->id);
    }

    private function profileWith(?string $accountId): void
    {
        PartnerProfile::factory()->for(User::factory()->create())->create([
            'stripe_account_id' => $accountId,
        ]);
    }
}
