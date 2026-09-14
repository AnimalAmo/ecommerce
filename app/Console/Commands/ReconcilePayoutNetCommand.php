<?php

namespace App\Console\Commands;

use App\Services\Payout\ReconcilePayoutNet;
use Illuminate\Console\Command;

/**
 * Riallinea il netto del registro a quello accreditato da Stripe.
 *
 * Gira ogni giorno prima del rilascio: al capture la balance transaction non
 * esiste ancora, quindi ogni riga nasce provvisoria e va confermata prima che
 * il bonifico la usi.
 */
class ReconcilePayoutNetCommand extends Command
{
    protected $signature = 'payouts:reconcile-net';

    protected $description = 'Rilegge da Stripe il netto accreditato e aggiorna il registro dei rilasci';

    public function handle(ReconcilePayoutNet $reconcile): int
    {
        $this->components->info('Righe riconciliate: '.$reconcile->run().'.');

        return self::SUCCESS;
    }
}
