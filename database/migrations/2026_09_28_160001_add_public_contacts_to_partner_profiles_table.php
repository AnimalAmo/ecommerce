<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recapiti pubblici del partner (risposta della cliente, 26/09/2026, punto
     * 6): voci NUOVE, mai il telefono o l'email con cui si è registrato né la
     * sede legale. Sono dati che il partner sceglie di mostrare, e si mostrano
     * solo col suo consenso; quelli di registrazione e fatturazione restano
     * privati.
     *
     * Sul profilo e non sulla struttura (decisione di Matteo, 27/09/2026):
     * appartengono al partner, come gli orari che stanno già qui.
     *
     * `public_address` è una colonna a sé e non `address`: quella è la sede
     * legale, un dato fiscale, e non si pubblica più. Telefono e WhatsApp in
     * E.164, quindi 32 caratteri bastano; email e sito con i tetti già usati
     * da `users.email` e da `payment_url`.
     *
     * Il consenso è una data e non un booleano: dice anche QUANDO il partner
     * l'ha dato. Null = non dato, e allora i recapiti restano salvati ma non
     * compaiono sulle schede. Tutto in coda dopo `opening_hours`, nel blocco
     * dei dati dell'attività.
     */
    public function up(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->string('public_phone', 32)->nullable()->after('opening_hours');
            $table->string('public_whatsapp', 32)->nullable()->after('public_phone');
            $table->string('public_email', 128)->nullable()->after('public_whatsapp');
            $table->string('public_website', 255)->nullable()->after('public_email');
            $table->string('public_address', 255)->nullable()->after('public_website');
            $table->timestamp('public_contacts_consent_at')->nullable()->after('public_address');
        });
    }

    public function down(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'public_phone',
                'public_whatsapp',
                'public_email',
                'public_website',
                'public_address',
                'public_contacts_consent_at',
            ]);
        });
    }
};
