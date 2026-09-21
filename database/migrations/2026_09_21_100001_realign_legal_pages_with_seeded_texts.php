<?php

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Il PageSeeder è passato a firstOrCreate: da qui in poi un db:seed non
     * porta più una revisione dei testi legali su un database già seminato.
     * L'ultima, l'art. 8 delle Condizioni Fornitore del 14/09 (provvigione
     * trattenuta all'origine), potrebbe non essere mai arrivata in produzione,
     * seminata l'08/09 e mai più riseminata.
     *
     * Ogni pagina del seeder già a database torna ai testi versionati (titolo
     * e corpo, it ed en) e alla loro data, ma solo se la sua data è più
     * vecchia di quella del seeder. Il pannello non è mai stato in produzione,
     * quindi una data più vecchia vuol dire testo seminato, non della cliente.
     * Una pagina senza data (il pannello la lascia togliere) non si tocca,
     * una che manca non si crea: è il lavoro del seeder.
     */
    public function up(): void
    {
        foreach (PageSeeder::definitions() as $slug => $versioned) {
            $page = Page::query()->where('slug', $slug)->first();

            if ($page?->last_updated_at === null || ! $page->last_updated_at->lt(Carbon::parse($versioned['last_updated_at']))) {
                continue;
            }

            $page->setTranslations('title', $versioned['title']);
            $page->setTranslations('body', $versioned['body']);
            $page->last_updated_at = $versioned['last_updated_at'];
            $page->save();
        }
    }

    /** I testi sostituiti non sono stati conservati: non c'è niente da ripristinare. */
    public function down(): void {}
};
