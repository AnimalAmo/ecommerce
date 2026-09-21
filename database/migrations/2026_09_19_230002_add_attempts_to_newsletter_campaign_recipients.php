<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tentativi di spedizione di un destinatario. Un errore temporaneo di
     * Mailgun (429, 5xx, rete) rimette la riga in coda invece di perderla, e
     * il contatore chiude il giro: dopo l'ultimo tentativo la riga è fallita.
     */
    public function up(): void
    {
        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
