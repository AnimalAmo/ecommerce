<?php

namespace App\Console\Commands;

use App\Models\Partner\PartnerProfile;
use App\Services\Payment\StripeConnectService;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;

/**
 * Rilegge da Stripe lo stato di ogni account connesso conosciuto.
 *
 * Lo specchio su `partner_profiles` si aggiorna con l'evento `account.updated`.
 * Quando quell'evento non arriva — endpoint Connect non ancora creato, segreto
 * sbagliato, endpoint disabilitato da Stripe dopo troppe consegne fallite — un
 * partner approvato continua a risultare non collegato e non vende. Questo
 * comando è il riallineamento manuale, da lanciare dopo aver sistemato i
 * webhook: un account che fallisce non ferma gli altri. Passa da
 * syncAccountState, quindi un partner che diventa pagabile qui mette in coda
 * anche la pubblicazione dei suoi servizi in attesa (P4).
 */
class ConnectSyncCommand extends Command
{
    protected $signature = 'animalamo:connect-sync';

    protected $description = 'Rilegge da Stripe lo stato degli account connessi dei partner';

    public function handle(StripeConnectService $connect): int
    {
        $synced = 0;
        $failed = 0;

        PartnerProfile::query()
            ->whereNotNull('stripe_account_id')
            ->each(function (PartnerProfile $profile) use ($connect, &$synced, &$failed): void {
                try {
                    $connect->syncAccountState((string) $profile->stripe_account_id);
                    $synced++;
                } catch (ApiErrorException $exception) {
                    $failed++;
                    $this->components->warn("{$profile->stripe_account_id}: {$exception->getMessage()}");
                }
            });

        $this->components->info("Account riallineati: {$synced}".($failed > 0 ? ", falliti: {$failed}." : '.'));

        return self::SUCCESS;
    }
}
