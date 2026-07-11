<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidature "Lavora con noi": il form pubblico salva qui e invia al
     * candidato l'email con il link (firmato) all'iscrizione B2B a step.
     * La moderazione superadmin prevista dalla spec arriverà come gate a
     * monte dell'invito (status pending → accettazione manuale).
     */
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name', 64);
            $table->string('last_name', 64);
            $table->string('email', 128)->index();
            $table->string('phone', 32);
            $table->string('website', 128)->nullable();
            $table->string('city', 64);
            $table->string('business_name', 128);
            $table->string('role', 64);
            $table->string('offer_type', 64);
            $table->text('description');
            // pending → invited (mail inviata) → registered (account creato).
            $table->string('status')->default('pending');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
