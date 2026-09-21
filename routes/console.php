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
