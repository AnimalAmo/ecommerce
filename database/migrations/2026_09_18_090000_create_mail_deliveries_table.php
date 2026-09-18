<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esito reale delle mail in uscita: una riga per (message-id, destinatario),
     * aperta quando il messaggio parte e aggiornata dai webhook Mailgun.
     *
     * Serve perché la ritenzione dei log Mailgun è di pochi giorni sul piano in
     * uso: senza questa tabella una consegna fallita di due settimane fa non
     * esiste più da nessuna parte, e "il partner non ha ricevuto l'invito" non
     * è verificabile. Lo stato della candidatura non basta: passa a INVITED
     * appena il job entra in coda, non a consegna avvenuta.
     */
    public function up(): void
    {
        Schema::create('mail_deliveries', function (Blueprint $table) {
            $table->id();
            // 191 e non il default 255: la unique qui sotto mette in chiave due
            // colonne, e in utf8mb4 due string(255) fanno 4080 byte. MySQL 8
            // (verificato su 8.0.35) le accetta, InnoDB con row format COMPACT
            // no — e i test girano su SQLite, che non ha nessuno di questi
            // limiti: il deploy sarebbe il primo posto in cui scoprirlo.
            // 191+191 = 3056 byte e resta comunque largo, gli indirizzi a form
            // sono limitati a 128 caratteri.
            $table->string('message_id', 191);
            $table->string('recipient', 191);
            // Classe del Mailable: distingue "invito partner" da "reset password"
            // senza dover interpretare l'oggetto, che è localizzato.
            $table->string('mailable')->nullable();
            $table->string('status')->default('sent');
            $table->string('severity')->nullable();
            $table->string('smtp_code')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('last_event_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'recipient']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_deliveries');
    }
};
