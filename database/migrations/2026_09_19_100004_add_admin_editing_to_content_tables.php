<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contenuti editabili dal pannello.
     *
     * pages: oltre alle tre legali, le "pagine libere" che la cliente crea da
     * sé (`kind` = free) e la colonna del piede in cui compaiono.
     *
     * content_blocks: i testi delle pagine pubbliche che oggi vivono nei file
     * lingua. Una riga esiste solo dove la cliente ha scritto qualcosa; dove non
     * c'è, il sito mostra il testo del file lingua — nessuna migrazione di massa
     * e nessun rischio di pagina vuota.
     *
     * articles: bozze (published_at nullo), categoria, copertina caricata dal
     * pannello (le vecchie stanno in public/img/news/{slug}.jpg), letture.
     *
     * faqs: le domande della pagina di assistenza non appartengono a nessun
     * prodotto (faqable nullo) e hanno un argomento. Domanda e risposta
     * diventano traducibili: i valori esistenti sono italiani e vengono
     * avvolti in {"it": ...}.
     *
     * community_posts: moderazione (nascosto, segnalazioni).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('kind', 16)->default('legal')->index();
            $table->string('footer_column', 16)->nullable();
            $table->boolean('is_published')->default(true);
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            // La chiave del file lingua che il blocco sovrascrive (es. about.hero_title).
            $table->string('key', 191)->unique();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->date('published_at')->nullable()->change();
            $table->string('category', 32)->nullable()->index();
            $table->string('cover_path')->nullable();
            $table->json('cover_alt')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('faqable_type')->nullable()->change();
            $table->unsignedBigInteger('faqable_id')->nullable()->change();
            $table->text('question')->change();
            $table->string('topic', 32)->nullable()->index();
        });

        DB::table('faqs')->orderBy('id')->each(function (object $faq): void {
            DB::table('faqs')->where('id', $faq->id)->update([
                'question' => $this->wrap($faq->question),
                'answer' => $this->wrap($faq->answer),
            ]);
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dateTime('hidden_at')->nullable()->index();
            $table->unsignedInteger('reports_count')->default(0);
            $table->dateTime('moderated_at')->nullable();
        });

        Schema::create('community_post_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            // Una segnalazione per utente: il contatore non si gonfia a clic ripetuti.
            $table->unique(['community_post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reports');

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropIndex(['hidden_at']);
            $table->dropColumn(['hidden_at', 'reports_count', 'moderated_at']);
        });

        DB::table('faqs')->orderBy('id')->each(function (object $faq): void {
            DB::table('faqs')->where('id', $faq->id)->update([
                'question' => $this->unwrap($faq->question),
                'answer' => $this->unwrap($faq->answer),
            ]);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['topic']);
            $table->dropColumn('topic');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'cover_path', 'cover_alt', 'views']);
        });

        Schema::dropIfExists('content_blocks');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'footer_column', 'is_published']);
        });
    }

    /** Idempotente: un valore già in forma {"it": ...} non viene riavvolto. */
    private function wrap(?string $value): string
    {
        $decoded = json_decode((string) $value, true);

        if (is_array($decoded)) {
            return (string) $value;
        }

        return json_encode(['it' => (string) $value], JSON_UNESCAPED_UNICODE);
    }

    private function unwrap(?string $value): string
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? (string) ($decoded['it'] ?? reset($decoded) ?: '') : (string) $value;
    }
};
