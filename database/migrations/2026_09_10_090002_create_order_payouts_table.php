<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro dei rilasci: una riga per riga d'ordine, con lo snapshot del
     * beneficiario e del suo account Stripe (il morph su order_items è
     * nullable, il legame vivo si perde). stripe_payout_id è condiviso da più
     * righe: con i direct charges non si trasferisce un ordine, si emette un
     * payout di un importo dal saldo di un account.
     */
    public function up(): void
    {
        Schema::create('order_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('partner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('stripe_account_id')->nullable();
            $table->unsignedInteger('gross_cents');
            $table->unsignedInteger('commission_cents')->default(0);
            $table->unsignedInteger('net_cents');
            $table->unsignedSmallInteger('commission_rate_bp');
            $table->string('status')->default('pending');
            $table->dateTime('release_at')->nullable();
            $table->string('stripe_payout_id')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'release_at']);
            $table->index(['partner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payouts');
    }
};
