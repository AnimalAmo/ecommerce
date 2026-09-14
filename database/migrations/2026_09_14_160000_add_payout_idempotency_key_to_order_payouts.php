<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chiave di idempotenza del tentativo di bonifico, persistita sulle righe.
 *
 * Derivarla dagli id delle righe mature al momento del giro non regge ai
 * ritentativi: basta che una riga nuova maturi nel frattempo perché il gruppo
 * cambi, la chiave cambi con lui e Stripe veda una richiesta mai vista — cioè
 * un secondo bonifico che ripaga le righe del primo. Scritta prima della
 * chiamata e riusata a ogni tentativo, la chiave resta quella del gruppo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->string('payout_idempotency_key')->nullable()->after('payout_attempts');
            $table->index('payout_idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->dropIndex(['payout_idempotency_key']);
            $table->dropColumn('payout_idempotency_key');
        });
    }
};
