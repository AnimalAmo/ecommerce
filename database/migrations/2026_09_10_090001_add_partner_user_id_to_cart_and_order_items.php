<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proprietario risolto della riga. Sul carrello serve a far rispettare il
     * proprietario unico PRIMA del pagamento (un direct charge nasce su un solo
     * account connesso); sull'ordine è lo snapshot che sopravvive alla
     * cancellazione del prodotto — oggi il morph nullable manda in 403 il
     * legittimo proprietario (PartnerBookingDetail.php:27).
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('partner_user_id')->nullable()->after('purchasable_id')
                ->constrained('users')->restrictOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('partner_user_id')->nullable()->after('purchasable_id')
                ->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_user_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_user_id');
        });
    }
};
