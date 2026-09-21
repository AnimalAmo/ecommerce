<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Newsletter interna: iscritti con prova del consenso, campagne, destinatari.
     *
     * La prova del consenso sono le colonne consent_*: testo accettato, IP e
     * user agent, e l'istante della conferma dal link della mail (double
     * opt-in). Senza queste una lista non è difendibile.
     *
     * Il flag `users.newsletter` raccolto da luglio è un boolean senza prova:
     * quei contatti entrano qui come `pending` e `legacy` = true, e diventano
     * iscritti solo se confermano dalla mail di cortesia. Chi non risponde non
     * entra in lista.
     */
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 5)->default('it');
            // pending | confirmed | unsubscribed | bounced | complained
            $table->string('status', 16)->default('pending')->index();
            // footer | registration | profile | legacy | admin
            $table->string('source', 16);
            $table->boolean('legacy')->default(false);
            $table->string('token', 64)->unique();
            $table->text('consent_text')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->string('consent_user_agent')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('confirmation_sent_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->dateTime('suppressed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->json('subject');
            $table->json('preheader')->nullable();
            $table->json('body')->nullable();
            // all | it | en
            $table->string('audience', 8)->default('all');
            // Invii per ora; 0 = tutti subito.
            $table->unsignedInteger('hourly_rate')->default(200);
            // draft | sending | sent | failed
            $table->string('status', 16)->default('draft')->index();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('test_sent_to')->nullable();
            $table->dateTime('test_sent_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            // queued | sent | failed | skipped
            $table->string('status', 16)->default('queued');
            $table->string('message_id', 191)->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            // La ripresa dopo un'interruzione si basa su questa chiave: un
            // destinatario già `sent` non riceve una seconda copia.
            $table->unique(['newsletter_campaign_id', 'newsletter_subscriber_id'], 'nl_campaign_subscriber_unique');
            // Nome esplicito: quello automatico fa 66 caratteri, MySQL ne accetta 64.
            $table->index(['newsletter_campaign_id', 'status'], 'nl_campaign_status_index');
        });

        $now = now();

        DB::table('users')
            ->where('newsletter', true)
            ->orderBy('id')
            ->each(function (object $user) use ($now): void {
                DB::table('newsletter_subscribers')->insertOrIgnore([
                    'email' => mb_strtolower($user->email),
                    'user_id' => $user->id,
                    'locale' => 'it',
                    'status' => 'pending',
                    'source' => 'legacy',
                    'legacy' => true,
                    'token' => bin2hex(random_bytes(32)),
                    'requested_at' => $user->created_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
    }
};
