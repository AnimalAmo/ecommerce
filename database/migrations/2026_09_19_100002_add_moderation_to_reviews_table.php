<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recensioni scritte dai clienti dal riepilogo ordine: entrano `pending` e
     * vanno sulla scheda solo dopo la moderazione. Le righe già presenti sono
     * quelle seedate e visibili oggi, quindi il default è `published`.
     *
     * `order_item_id` è unique: una recensione per riga d'ordine, ed è la prova
     * che chi scrive ha davvero comprato.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status', 32)->default('published')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->dateTime('flagged_at')->nullable();
            $table->dateTime('moderated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_item_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'flagged_at', 'moderated_at']);
        });
    }
};
