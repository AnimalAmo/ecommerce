<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quando il netto della riga è stato confermato dalla balance transaction.
 *
 * Al momento del capture Stripe non l'ha ancora creata: l'addebito esiste, il
 * suo `balance_transaction` è null, e il registro nasce con lordo meno
 * provvigione — cioè qualche centesimo più di quanto il saldo conterrà. Questa
 * colonna distingue una riga ancora provvisoria da una riconciliata, e null
 * significa "da rileggere da Stripe".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->dateTime('net_reconciled_at')->nullable()->after('net_cents');
        });
    }

    public function down(): void
    {
        Schema::table('order_payouts', function (Blueprint $table) {
            $table->dropColumn('net_reconciled_at');
        });
    }
};
