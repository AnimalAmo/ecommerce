<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metodo di pagamento salvato (Profilo → Dati pagamento). Il PAN non transita
 * MAI dal nostro server: la carta vive su Stripe (customer + payment method),
 * qui restano solo i riferimenti e i dati mascherati che il design mostra.
 * Il design prevede una sola carta per utente, quindi colonne e non tabella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_customer_id')->nullable()->index()->after('is_active');
            $table->string('stripe_payment_method_id')->nullable()->after('stripe_customer_id');
            $table->string('card_brand', 30)->nullable()->after('stripe_payment_method_id');
            $table->string('card_last4', 4)->nullable()->after('card_brand');
            $table->unsignedTinyInteger('card_exp_month')->nullable()->after('card_last4');
            $table->unsignedSmallInteger('card_exp_year')->nullable()->after('card_exp_month');
            $table->string('card_holder')->nullable()->after('card_exp_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_payment_method_id',
                'card_brand',
                'card_last4',
                'card_exp_month',
                'card_exp_year',
                'card_holder',
            ]);
        });
    }
};
