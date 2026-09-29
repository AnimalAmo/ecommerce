<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Le tre tabelle del catalogo che un publisher di famiglia scrive. */
    private const TABLES = ['structures', 'events', 'smartbox_packages'];

    /**
     * Galleria foto sulla riga di catalogo («Vedere tutte le foto»,
     * segnalazione del 29/09/2026 su /eventi/attivita/bauclub-27).
     *
     * Il pulsante non apriva niente: la riga di catalogo conosceva solo la
     * copertina (`img`/`hero_img`), e le altre foto caricate nel wizard
     * vivevano soltanto nella bozza. Leggerle da lì avrebbe messo online le
     * foto di una modifica non ancora completata; la colonna invece la scrive
     * il publisher insieme alla copertina, quindi la scheda mostra la versione
     * pubblicata.
     *
     * Lista di path sul disco public, nell'ordine della bozza (la prima è la
     * copertina). NULL sulle schede del catalogo demo, che hanno la sola
     * copertina del template.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            // Su MySQL il DDL non è transazionale: se il travaso si interrompe,
            // la colonna resta creata e la migrazione non risulta eseguita. La
            // guardia rende rilanciabile anche questa metà.
            if (! Schema::hasColumn($table, 'gallery')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->json('gallery')->nullable()->after('hero_img');
                });
            }

            $this->copyFromDrafts($table);
        }
    }

    public function down(): void
    {
        // Le foto non si perdono: la fonte resta sulla bozza
        // (`structure_drafts.photos`), e una nuova `up()` le ricopia.
        // Stessa guardia di up(): dopo un up() interrotto a metà la colonna
        // c'è solo su alcune tabelle.
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'gallery')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('gallery');
                });
            }
        }
    }

    /**
     * Travaso delle schede già pubblicate: senza, il pulsante sparirebbe da
     * tutte finché il partner non ripubblica. Bozza e riga sono legate da
     * `structure_draft_id` (la chiave dell'updateOrCreate dei publisher),
     * quindi si copia esattamente quello che una ripubblicazione copierebbe.
     *
     * Idempotente: si toccano solo le righe con la colonna ancora nulla, così
     * un rilancio non sovrascrive quello che una ripubblicazione ha già
     * scritto. Le schede del catalogo demo (`structure_draft_id` nullo) non
     * hanno una bozza e restano fuori.
     *
     * Effetto accettato, lo stesso del travaso della descrizione dettagliata:
     * la bozza è la copia di lavoro. Se il partner ha una modifica aperta con
     * lo step foto già salvato, va online la sua lista nuova. Non ci sono file
     * mancanti: una foto tolta prima di oggi era già stata cancellata dal
     * disco insieme alla voce della bozza, e una copertina tolta resta in
     * testa alla galleria perché la legge `hero_img`
     * (HasCatalogImages::galleryImageUrls()).
     */
    private function copyFromDrafts(string $table): void
    {
        DB::table($table)
            ->join('structure_drafts', 'structure_drafts.id', '=', "{$table}.structure_draft_id")
            ->whereNull("{$table}.gallery")
            ->whereNotNull('structure_drafts.photos')
            ->select("{$table}.id as row_id", 'structure_drafts.photos as source')
            ->chunkById(200, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $photos = json_decode((string) $row->source, true);

                    if (! is_array($photos) || $photos === []) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->row_id)->update([
                        'gallery' => json_encode(array_values($photos)),
                    ]);
                }
            }, "{$table}.id", 'row_id');
    }
};
