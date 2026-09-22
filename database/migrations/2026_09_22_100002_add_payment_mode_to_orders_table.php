<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La modalità si scrive una volta, quando l'ordine nasce, e non si ricava
     * mai dal flag attuale del partner: se il partner cambia idea, i suoi
     * ordini vecchi non devono cambiare significato. Il link è copiato per lo
     * stesso motivo. `checkout_token` (ULID) dà al ramo offline l'idempotenza
     * che nel ramo online dà l'unique su order_payments.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_mode', 16)->default('online')->after('status');
            $table->string('partner_payment_url', 255)->nullable()->after('payment_mode');
            $table->string('checkout_token', 26)->nullable()->unique()->after('partner_payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_checkout_token_unique');
            $table->dropColumn(['payment_mode', 'partner_payment_url', 'checkout_token']);
        });
    }
};
