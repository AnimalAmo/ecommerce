<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_drafts', function (Blueprint $table) {
            $table->id();
            // Futuro: legame col partner autenticato (oggi la bozza è tracciata in sessione).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Stato del wizard: draft finché l'utente non completa lo step 11.
            $table->string('status')->default('draft'); // draft | completed
            $table->unsignedTinyInteger('current_step')->default(0);

            // Crea servizio + tipologia struttura
            $table->string('service_category')->nullable(); // struttura | attivita | servizi | smartbox
            $table->string('type')->nullable();             // hotel | bb | agriturismo

            // Titolo / luogo
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip')->nullable();
            $table->string('license')->nullable();

            // Descrizione
            $table->text('description')->nullable();

            // Info stanze
            $table->json('rooms')->nullable();
            $table->string('checkin_from')->nullable();
            $table->string('checkin_to')->nullable();
            $table->string('checkout_from')->nullable();
            $table->string('checkout_to')->nullable();

            // Cancellazione
            $table->string('cancellation_when')->nullable();

            // Servizi struttura
            $table->json('services')->nullable();
            $table->json('additional_services')->nullable();
            $table->text('additional_other')->nullable();
            $table->json('meal_times')->nullable();
            $table->json('rules')->nullable();

            // Servizi animali
            $table->json('animal_services')->nullable();
            $table->text('animal_services_other')->nullable();

            // Smartbox
            $table->string('smartbox_consent')->nullable();
            $table->json('smartbox_types')->nullable();

            // Foto (percorsi salvati)
            $table->json('photos')->nullable();

            // Metodo di pagamento
            $table->string('account_holder')->nullable();
            $table->string('iban')->nullable();
            $table->string('sdi')->nullable();
            $table->string('bic')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_drafts');
    }
};
