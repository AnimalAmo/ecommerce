<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tentativi di bonifico già spesi su una riga. Serve a distinguere un
 * fallimento transitorio (saldo non ancora capiente, rate limit, 500 di
 * Stripe) da uno definitivo: senza un contatore, la sola scelta sarebbe fra
 * riprovare in eterno e chiudere la riga al primo errore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->unsignedSmallInteger('payout_attempts')->default(0)->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->dropColumn('payout_attempts');
        });
    }
};
