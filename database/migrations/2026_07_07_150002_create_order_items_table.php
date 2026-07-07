<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Righe ordine: snapshot display completo (title/foto/tipo/location) così la
     * lista ordini resta leggibile anche se il prodotto sparisce dal catalogo
     * (morph nullable). booked_from/booked_until alimentano il bucket
     * programma/passati del profilo (calcolati per famiglia in CreateOrderItemsPipe).
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('purchasable');
            // Snapshot display (da CartItemData al momento dell'acquisto).
            $table->string('title');
            $table->string('photo_url')->nullable();
            $table->string('product_type'); // valore ProductType
            $table->string('location')->nullable();
            $table->unsignedInteger('price_cents');
            $table->boolean('is_gift')->default(false);
            // Stesse shape canoniche del carrello (incl. options.gift per le righe regalo).
            $table->json('options')->nullable();
            $table->dateTime('booked_from')->nullable();
            $table->dateTime('booked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
