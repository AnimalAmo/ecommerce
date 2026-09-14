<?php

namespace App\Console\Commands;

use App\Enums\PayoutStatus;
use App\Models\OrderPayout\OrderPayout;
use Illuminate\Console\Command;

/**
 * Rimette in coda i bonifici che hanno esaurito i tentativi automatici.
 *
 * È la via d'uscita umana del registro: quando la causa è stata sistemata —
 * saldo capiente, account ripristinato — le righe tornano `Pending` e il giro
 * successivo dello scheduler le bonifica. `last_error` resta, come storia.
 */
class RetryFailedPayoutsCommand extends Command
{
    protected $signature = 'payouts:retry';

    protected $description = 'Rimette in coda i bonifici dichiarati definitivamente falliti';

    public function handle(): int
    {
        $revived = OrderPayout::query()
            ->where('status', PayoutStatus::Failed)
            ->update([
                'status' => PayoutStatus::Pending,
                'payout_attempts' => 0,
                'failed_at' => null,
            ]);

        $this->components->info("Righe rimesse in coda: {$revived}.");

        return self::SUCCESS;
    }
}
