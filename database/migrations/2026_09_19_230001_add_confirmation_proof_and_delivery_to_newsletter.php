<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Due prove, non una: consent_ip / consent_user_agent restano quelle della
     * richiesta (il form compilato), confirmation_* sono quelle del clic sul
     * link della mail. Sovrascrivere le prime con le seconde cancellerebbe
     * metà della prova del double opt-in.
     *
     * delivered_*: le consegne confermate dal webhook Mailgun, base della
     * percentuale di aperture ("251 su 608 consegnate").
     *
     * Infine `users.newsletter` torna vero solo per chi ha confermato: il
     * travaso dei vecchi flag è già in newsletter_subscribers (legacy), e il
     * resto del sito legge quella colonna come "iscritto alla newsletter".
     */
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('confirmation_ip', 45)->nullable()->after('confirmed_at');
            $table->string('confirmation_user_agent')->nullable()->after('confirmation_ip');
        });

        Schema::table('newsletter_campaigns', function (Blueprint $table) {
            $table->unsignedInteger('delivered_count')->default(0)->after('sent_count');
        });

        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->dateTime('delivered_at')->nullable()->after('sent_at');
        });

        DB::table('users')
            ->where('newsletter', true)
            ->whereNotIn('id', DB::table('newsletter_subscribers')
                ->where('status', 'confirmed')
                ->whereNotNull('user_id')
                ->select('user_id'))
            ->update(['newsletter' => false]);
    }

    /**
     * Prima di togliere le colonne riaccende `users.newsletter` per chi è
     * ancora in lista (in attesa o confermato): la up() lo aveva spento a chi
     * non aveva confermato, e dopo il rollback il vecchio flag torna a essere
     * la sola traccia dell'iscrizione che il resto del sito legge.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('newsletter', false)
            ->whereIn('id', DB::table('newsletter_subscribers')
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNotNull('user_id')
                ->select('user_id'))
            ->update(['newsletter' => true]);

        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });

        Schema::table('newsletter_campaigns', function (Blueprint $table) {
            $table->dropColumn('delivered_count');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['confirmation_ip', 'confirmation_user_agent']);
        });
    }
};
