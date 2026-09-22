<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagamento online facoltativo (richiesta della cliente, 22/09/2026). Il
     * default true lascia ogni partner già registrato com'era: online, con
     * Stripe obbligatorio per pubblicare. `payment_url` è il sito facoltativo
     * dove il cliente paga o prenota quando paga direttamente il partner.
     */
    public function up(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->boolean('online_payment')->default(true)->after('commission_min_cents');
            $table->string('payment_url', 255)->nullable()->after('online_payment');
        });
    }

    public function down(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn(['online_payment', 'payment_url']);
        });
    }
};
