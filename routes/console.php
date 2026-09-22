<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bonifici ai partner: le righe mature del giorno prima, un payout per account.
// Prima del rilascio: al capture la balance transaction non esiste ancora,
// quindi ogni riga nasce con un netto provvisorio da confermare.
Schedule::command('payouts:reconcile-net')->dailyAt('05:45');
Schedule::command('payouts:release')->dailyAt('06:00');

// Cache su database: le righe scadute delle chiavi usa e getta (anti-replay dei
// webhook Mailgun, catena della newsletter) non le cancella nessun altro.
Schedule::command('animalamo:prune-expired-cache')->dailyAt('04:30');

// Pubblicazione automatica al collegamento Stripe (P4): la rete di sicurezza
// del job lanciato da account.updated, per un webhook perso o un worker che in
// produzione non c'è. Idempotente, e senza sovrapposizioni tra due giri lenti.
// Il lucchetto scade dopo dieci minuti come la cadenza: quello di default dura
// un giorno, e un comando ucciso a metà (deploy, OOM) lo lascerebbe nella cache
// spegnendo la rete di sicurezza fino al giorno dopo, in silenzio.
Schedule::command('animalamo:publish-awaiting-drafts')->everyTenMinutes()->withoutOverlapping(10);
