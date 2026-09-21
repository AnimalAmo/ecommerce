<?php

namespace App\Console\Commands;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Toglie dalla tabella della cache le righe scadute (schedulato ogni notte).
 *
 * Lo store database cancella una riga scaduta solo quando qualcuno rilegge
 * quella chiave. Le chiavi usa e getta non le rilegge nessuno: l'anti-replay
 * dei webhook Mailgun (una per evento, MailgunWebhookSignature) e gli anelli
 * newsletter:chain:* (uno per lotto, CampaignSender). Senza questa pulizia la
 * tabella crescerebbe di qualche riga per ogni mail spedita, per sempre.
 */
class PruneExpiredCacheCommand extends Command
{
    protected $signature = 'animalamo:prune-expired-cache';

    protected $description = 'Cancella le righe scadute della cache su database';

    /** Righe per DELETE: la prima pulizia di una tabella grande non la blocca a lungo. */
    private const CHUNK = 1000;

    public function handle(): int
    {
        $store = Cache::store()->getStore();

        if (! $store instanceof DatabaseStore) {
            $this->components->info('La cache non è su database: niente da cancellare.');

            return self::SUCCESS;
        }

        $table = (string) config('cache.stores.'.config('cache.default').'.table', 'cache');
        // Scaduta per DatabaseStore: expiration <= adesso.
        $now = now()->getTimestamp();
        $deleted = 0;

        do {
            $batch = $store->getConnection()->table($table)
                ->where('expiration', '<=', $now)
                ->limit(self::CHUNK)
                ->delete();

            $deleted += $batch;
        } while ($batch === self::CHUNK);

        $this->components->info("Righe scadute cancellate: {$deleted}.");

        return self::SUCCESS;
    }
}
