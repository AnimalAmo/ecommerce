<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Descrizione dettagliata di attività e servizi professionali sulla riga di
     * catalogo (audit dei flussi, 28/09/2026, difetto W1).
     *
     * Il difetto: `ActivityDescription::next()` rende la descrizione
     * dettagliata OBBLIGATORIA per il ramo Attività e la salva su
     * `structure_drafts.detailed_description`, ma su `events` la gemella non
     * c'era ed EventPublisher non la nominava: il partner compilava un campo
     * senza il quale non avanzava e che nessun cliente leggeva, mentre la
     * scheda pubblica ristampava la descrizione breve nella sezione «Attività».
     * È la stessa trappola già pagata con categorie, zona e tipologie; la
     * smartbox la gemella ce l'ha da sempre (`extended_description`).
     *
     * `text` e non `string`: il testo è libero e tradotto it/en con spatie, che
     * serializza le lingue in JSON dentro la colonna, come le sorelle
     * `activity_categories_other`, `operating_area` ed `event_categories_other`.
     */
    public function up(): void
    {
        // Su MySQL il DDL non è transazionale: se il travaso si interrompe, la
        // colonna resta creata e la migrazione non risulta eseguita. La
        // guardia rende rilanciabile anche questa metà.
        if (! Schema::hasColumn('events', 'detailed_description')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('detailed_description')->nullable()->after('description');
            });
        }

        $this->copyFromDrafts();
    }

    public function down(): void
    {
        // Il testo copiato qui non va perso: la fonte resta sulla bozza
        // (`structure_drafts.detailed_description`), e una nuova `up()` lo
        // ricopia.
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('detailed_description');
        });
    }

    /**
     * Travaso delle attività già pubblicate: senza, il testo che il partner ha
     * scritto resta nella bozza finché non ripubblica, e la seconda sezione
     * della scheda — che da oggi non ripete più la breve — sparirebbe dalla
     * pagina di tutte le attività pubblicate prima di questa correzione.
     *
     * Bozza e riga di catalogo sono legate da `events.structure_draft_id`
     * (unique: è la chiave dell'updateOrCreate di EventPublisher), quindi il
     * travaso copia esattamente quello che una ripubblicazione copierebbe. La
     * guardia sul tipo è la stessa del publisher: solo le righe `activity`,
     * perché un evento vero non ha la descrizione dettagliata e una bozza
     * passata da Attività a Evento se la può portare addosso.
     *
     * Letterale 'activity' e non ProductType::Activity: una migrazione non deve
     * rompersi il giorno che l'enum cambia.
     *
     * Idempotente: si toccano solo le righe con la colonna ancora nulla, quindi
     * rilanciarla dopo un'interruzione riprende da dove era e non sovrascrive
     * quello che una ripubblicazione ha già scritto. Le attività del catalogo
     * demo (`structure_draft_id` nullo) non hanno una bozza e restano fuori.
     *
     * Effetto accettato: la bozza è la copia di lavoro. Se il partner ha
     * riaperto un servizio pubblicato, ha riscritto la dettagliata e non ha
     * completato la modifica (o la modifica aspetta Stripe), va online il
     * testo nuovo mentre il resto della scheda resta alla versione pubblicata.
     * È comunque testo suo, per la sua scheda; distinguere le modifiche
     * abbandonate richiederebbe un'euristica sui timestamp che il
     * completamento stesso sporca.
     */
    private function copyFromDrafts(): void
    {
        DB::table('events')
            ->join('structure_drafts', 'structure_drafts.id', '=', 'events.structure_draft_id')
            ->where('events.type', 'activity')
            ->whereNull('events.detailed_description')
            ->whereNotNull('structure_drafts.detailed_description')
            ->select('events.id as event_id', 'structure_drafts.detailed_description as source')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $translations = self::translations($row->source);

                    if ($translations === null) {
                        continue;
                    }

                    DB::table('events')->where('id', $row->event_id)->update([
                        'detailed_description' => $translations,
                    ]);
                }
            }, 'events.id', 'event_id');
    }

    /**
     * Il valore di bozza nella forma di spatie, con le sole lingue compilate
     * (come `FamilyPublisher::translations()`); null quando non ne resta
     * nessuna, così la colonna resta nulla e la scheda nasconde la sezione.
     *
     * Un testo piano (bozza precedente alla conversione a translatable) viene
     * avvolto in {"it": …}, come fa `normalize_legacy_translatable_columns`.
     */
    private static function translations(?string $raw): ?string
    {
        $decoded = json_decode((string) $raw, true);
        $values = is_array($decoded) ? $decoded : ['it' => $raw];
        $values = array_filter($values, fn ($value): bool => is_string($value) && filled($value));

        return $values === [] ? null : json_encode($values, JSON_UNESCAPED_UNICODE);
    }
};
