<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dati anagrafici/fiscali e di pagamento del partner B2B (1:1 con users, ruolo
 * partner). Separati da `users` — di forma B2C — per non mescolare i due domini.
 * Alimentano le pagine Profilo: info personali (fiscali) + metodo di pagamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Anagrafica/fiscale (Informazioni personali)
            $table->string('business_name')->nullable();   // Ragione Sociale
            $table->string('vat')->nullable();              // Partita IVA
            $table->string('tax_code')->nullable();         // Codice Fiscale
            $table->string('pec')->nullable();
            $table->string('sdi')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip')->nullable();

            // Metodo di pagamento
            $table->string('account_holder')->nullable();   // Titolare Conto
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
