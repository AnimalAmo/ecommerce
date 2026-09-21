<?php

use App\Services\Content\ArticleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le copertine di Animal Times passano alla media library.
     *
     * Fino a oggi ogni articolo aveva due ritagli versionati in
     * public/img/news/{slug}.jpg e {slug}-hero.jpg, letti per convenzione di
     * nome. Adesso la foto sta in database/seeders/content/articles/{slug}.jpg
     * e diventa la media `cover` dell'articolo: i ritagli li producono le
     * conversioni. Gli articoli già a database la ricevono qui; su un database
     * nuovo la carica l'ArticleSeeder. Rilanciata dopo un'interruzione salta
     * chi la copertina ce l'ha già e completa gli altri.
     *
     * `cover_path`, aggiunta dalle fondamenta del pannello e mai scritta da
     * nessuno, se ne va: un file caricato non si tiene come path in colonna.
     */
    public function up(): void
    {
        app(ArticleService::class)->importSeedCovers();

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }

    /** Le media restano: toglierle distruggerebbe copertine caricate dalla cliente. */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('cover_path')->nullable();
        });
    }
};
