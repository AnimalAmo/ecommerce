<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Righe carrello: morph verso il catalogo acquistabile (structure/event/smartbox_package,
     * alias della morph map in AppServiceProvider). Quantità fissa 1 per riga, niente colonna.
     * Niente unique a db: il dedup (purchasable + options canonicalizzate + is_gift) è applicativo.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->morphs('purchasable');
            // Flusso regalo separato: le viste filtrano sul flag, mai miste.
            $table->boolean('is_gift')->default(false);
            // Totale riga snapshot in cents (riprezzato server-side a ogni add/update/merge).
            $table->unsignedInteger('price_cents');
            // Vocabolario canonico per famiglia (chiavi ksortate): date, guests, animals, gift, ...
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
