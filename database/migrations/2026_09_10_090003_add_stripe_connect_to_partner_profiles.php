<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anagrafica Connect del partner. I due flag arrivano dal webhook
     * account.updated: sono uno specchio, non una fonte — la verità sta su
     * Stripe. Le colonne di provvigione sono nullable con fallback su
     * config/commerce.php: la regola nasce globale e resta derogabile per
     * singolo partner senza una migrazione.
     */
    public function up(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique()->after('bic');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->json('stripe_requirements_due')->nullable()->after('stripe_payouts_enabled');
            $table->unsignedSmallInteger('commission_rate_bp')->nullable()->after('stripe_requirements_due');
            $table->unsignedInteger('commission_min_cents')->nullable()->after('commission_rate_bp');
        });
    }

    public function down(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_requirements_due',
                'commission_rate_bp',
                'commission_min_cents',
            ]);
        });
    }
};
