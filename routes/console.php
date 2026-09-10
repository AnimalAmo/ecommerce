<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bonifici ai partner: le righe mature del giorno prima, un payout per account.
Schedule::command('payouts:release')->dailyAt('06:00');
