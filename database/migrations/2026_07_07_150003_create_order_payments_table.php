<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagamento dell'ordine (capture-first: creato già Completed dalla pipeline).
     * gateway_session_id indicizzato: chiave di riconciliazione dei webhook
     * (PaymentIntent id Stripe / order id PayPal).
     */
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method'); // cast PaymentMethod
            $table->string('status')->default('pending'); // cast PaymentStatus
            $table->unsignedInteger('amount_cents');
            $table->string('transaction_id')->nullable();
            $table->string('gateway_session_id')->nullable()->index();
            $table->string('provider')->nullable();
            // Idempotenza capture-first: lo stesso incasso (provider + session
            // id del gateway) non può MAI generare due ordini pagati — replay
            // del callback o del return URL Klarna inclusi. NULL multipli ok.
            $table->unique(['provider', 'gateway_session_id']);
            // Subset safe della risposta del provider (mai l'oggetto intero).
            $table->json('provider_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
