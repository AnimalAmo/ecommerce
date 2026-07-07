<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ordini (checkout step 4): testata con snapshot buyer autonomo dal profilo
     * (guest checkout permesso → user_id nullable). Soldi SEMPRE int cents.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Numero umano sequenziale (ORD-000042) generato in creating (SequentialNumberGenerator).
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // cast OrderStatus
            // Flusso regalo separato dal carrello normale: l'ordine appartiene a uno dei due.
            $table->boolean('is_gift')->default(false);
            // Snapshot buyer (form step 1 checkout), non i dati profilo correnti.
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country')->default('Italia');
            $table->unsignedInteger('total_cents');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
