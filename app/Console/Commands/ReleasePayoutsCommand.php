<?php

namespace App\Console\Commands;

use App\Services\Payout\ReleaseMaturedPayouts;
use Illuminate\Console\Command;

/**
 * Bonifica ai partner ciò che è maturato (schedulato ogni mattina).
 * Il "quando" è la sola leva che resta ad AnimalAmo: con i direct charges il
 * denaro è del partner dal primo secondo, e questo comando decide il momento
 * in cui esce dal suo saldo Stripe verso la sua banca.
 */
class ReleasePayoutsCommand extends Command
{
    protected $signature = 'payouts:release';

    protected $description = 'Emette i bonifici maturati verso i partner';

    public function handle(ReleaseMaturedPayouts $payouts): int
    {
        $released = $payouts->run();

        $this->components->info("Payout emessi: {$released}.");

        return self::SUCCESS;
    }
}
