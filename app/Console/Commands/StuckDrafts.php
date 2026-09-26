<?php

namespace App\Console\Commands;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Perché una scheda compilata non compare sul sito (segnalazione della
 * cliente, 29/09/2026: «alcune strutture hanno collegato Stripe ma la scheda
 * non viene pubblicata»).
 *
 * Il comando non indovina: divide le bozze ferme in tre gruppi, ognuno con una
 * causa diversa e una cura diversa.
 *
 *  1. SENZA SEGNALE — la bozza è pronta, il partner è arrivato in fondo al
 *     wizard, ma `publish_requested_at` è vuoto. È il buco lasciato dalla
 *     migrazione 2026_09_22_100003, che ha aggiunto la colonna senza
 *     riempirla: prima di quella data il rollback di DraftCompleter riportava
 *     lo status a `draft` e non scriveva alcun segnale. Queste bozze sono
 *     invisibili a tutto — alla rete di sicurezza schedulata (scopeAwaitingPublication),
 *     a "I miei servizi" (scopeListableFor) e al contatore della dashboard.
 *     Il partner ha compilato undici step e il servizio non esiste da nessuna
 *     parte. È `--fix` a recuperarle.
 *
 *  2. PRONTE MA FERME — hanno il segnale e il partner PUÒ già pubblicare.
 *     Dovevano andare a catalogo da sole: o il cron `schedule:run` non gira in
 *     produzione, o i flag Stripe erano già a posto quando il webhook è
 *     arrivato e `publishAwaitingDraftsOnPayable()` è uscito subito
 *     (esce se i flag non sono CAMBIATI). Si sbloccano con
 *     `animalamo:publish-awaiting-drafts`. Se quel comando ne pubblica,
 *     il cron non sta girando: è la diagnosi, non la cura.
 *
 *  3. IN ATTESA MA INCOMPLETE — hanno il segnale e mancano i dati minimi per
 *     il catalogo. La rete di sicurezza le ritenta ogni dieci minuti e non
 *     andranno mai a buon fine: le deve correggere il partner. Intanto la sua
 *     schermata dice "in attesa del collegamento Stripe", che è falso.
 */
class StuckDrafts extends Command
{
    protected $signature = 'animalamo:stuck-drafts
        {--fix : Scrive publish_requested_at sulle bozze del gruppo 1, così la pubblicazione differita le vede}';

    protected $description = 'Elenca le bozze partner pronte che non sono mai arrivate a catalogo, e perché';

    public function handle(): int
    {
        $drafts = StructureDraft::query()
            ->whereNotNull('user_id')
            ->with('user.partnerProfile')
            ->orderBy('id')
            ->get()
            ->reject(fn (StructureDraft $draft): bool => self::isPublished($draft));

        $withoutSignal = $drafts->filter(fn (StructureDraft $draft): bool => ! $draft->isAwaitingPublication() && self::looksFinished($draft));
        $awaiting = $drafts->filter(fn (StructureDraft $draft): bool => $draft->isAwaitingPublication());

        $this->report(
            'Senza segnale: pronte, mai pubblicate, invisibili anche al partner',
            $withoutSignal,
            'Nessuna: ogni bozza pronta ha il suo segnale.',
        );

        $this->report(
            'Pronte ma ferme: il partner può già pubblicare',
            $awaiting->filter(fn (StructureDraft $draft): bool => $draft->user?->partnerProfile?->canPublish() === true),
            'Nessuna: niente di pubblicabile è rimasto indietro.',
        );

        $this->report(
            'In attesa ma incomplete: mancano i dati minimi, la ripubblicazione non andrà mai a buon fine',
            $awaiting->reject(fn (StructureDraft $draft): bool => self::isPublishable($draft)),
            'Nessuna: le bozze in attesa hanno tutte i dati per andare a catalogo.',
        );

        if ($withoutSignal->isNotEmpty()) {
            $this->components->warn(
                $this->option('fix')
                    ? 'Scrivo il segnale sulle bozze del primo gruppo.'
                    : 'Riesegui con --fix per dare il segnale alle bozze del primo gruppo.',
            );
        }

        if ($this->option('fix') && $withoutSignal->isNotEmpty()) {
            StructureDraft::query()->whereKey($withoutSignal->modelKeys())->update(['publish_requested_at' => now()]);

            $this->components->info($withoutSignal->count().' bozze recuperate. Ora lancia animalamo:publish-awaiting-drafts.');
        }

        return self::SUCCESS;
    }

    /** @param  Collection<int, StructureDraft>  $drafts */
    private function report(string $title, Collection $drafts, string $whenEmpty): void
    {
        $this->newLine();
        $this->components->info($title);

        if ($drafts->isEmpty()) {
            $this->line('  '.$whenEmpty);

            return;
        }

        $this->table(
            ['id', 'partner', 'famiglia', 'step', 'stato', 'pubblicabile', 'può pubblicare'],
            $drafts->map(fn (StructureDraft $draft): array => [
                $draft->id,
                $draft->user?->email ?? '—',
                $draft->family(),
                $draft->current_step.'/'.$draft->finalStep(),
                $draft->status,
                self::isPublishable($draft) ? 'sì' : 'NO',
                $draft->user?->partnerProfile?->canPublish() === true ? 'sì' : 'NO',
            ])->all(),
        );
    }

    /**
     * Il partner è arrivato in fondo, non ha abbandonato a metà. Serve perché
     * `--fix` non deve svegliare una bozza lasciata a metà: finirebbe in "I
     * miei servizi" col badge di attesa e la rete di sicurezza la ritenterebbe
     * ogni dieci minuti.
     *
     * `finalStep() - 1` e non `finalStep()`: lo step di chiusura lo scrive
     * DraftCompleter, e quando la transazione rollbacka non viene scritto —
     * per le strutture nemmeno da `skip()`, che non fa saveStep. L'ultimo step
     * certo è dunque il penultimo (struttura: foto a 10; attività:
     * cancellazione a 10; smartbox: foto a 11).
     */
    private static function looksFinished(StructureDraft $draft): bool
    {
        return self::isPublishable($draft)
            && ($draft->status === StructureDraft::STATUS_COMPLETED || $draft->current_step >= $draft->finalStep() - 1);
    }

    /** Esiste già la riga a catalogo di questa bozza? (withHidden: anche se sospesa o in moderazione) */
    private static function isPublished(StructureDraft $draft): bool
    {
        return match ($draft->family()) {
            'attivita' => Event::withHidden()->where('structure_draft_id', $draft->id)->exists(),
            'smartbox' => SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->exists(),
            default => Structure::withHidden()->where('structure_draft_id', $draft->id)->exists(),
        };
    }

    /**
     * Gli stessi requisiti minimi di DraftPublisher::isPublishable(), che è
     * privato. Se quella regola cambia, va cambiata anche qui: il comando
     * mente se le due divergono.
     */
    private static function isPublishable(StructureDraft $draft): bool
    {
        if (blank($draft->getTranslation('name', 'it'))) {
            return false;
        }

        return match ($draft->family()) {
            'attivita' => $draft->date_start !== null,
            'smartbox' => filled($draft->price),
            default => filled($draft->rooms),
        };
    }
}
