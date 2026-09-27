<?php

namespace App\Console\Commands;

use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\AwaitingDraftPublisher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rete di sicurezza della pubblicazione automatica (P4). Il job
 * PublishAwaitingDrafts parte da account.updated e dal cambio di modalità, ma
 * un worker non è garantito in produzione (vedi ResetPasswordMail) e un
 * webhook può non arrivare. Ogni dieci minuti si pubblicano in sincrono le
 * bozze in attesa dei partner attivi che ora possono pubblicare.
 *
 * Si saltano le bozze senza proprietario (finestra da ospite prima
 * dell'08/09/2026), perché non c'è un partner che le venda, e quelle dei
 * partner disattivati o anonimizzati. AwaitingDraftPublisher ripete comunque
 * il controllo per ogni bozza. Un partner che fallisce non ferma gli altri.
 *
 * Il gate di pubblicazione è per famiglia (richiesta della cliente del
 * 27/09/2026 sulle smartbox): qui serve solo a non chiamare il publisher per
 * chi non ha niente da pubblicare, mentre la parola definitiva su ogni singola
 * bozza resta di AwaitingDraftPublisher, che la scarta in silenzio. Il
 * conteggio stampato conta le bozze andate davvero a catalogo.
 */
class PublishAwaitingDraftsCommand extends Command
{
    protected $signature = 'animalamo:publish-awaiting-drafts';

    protected $description = 'Pubblica i servizi in attesa di Stripe dei partner che ora possono pubblicare';

    public function handle(AwaitingDraftPublisher $publisher): int
    {
        // Le bozze in attesa con la loro famiglia, non i soli id dei partner:
        // dal 27/09/2026 il gate è per famiglia, quindi sapere chi ha qualcosa
        // in attesa non basta — serve sapere se ha qualcosa di pubblicabile.
        $awaiting = StructureDraft::query()
            ->awaitingPublication()
            ->whereNotNull('user_id')
            ->get(['id', 'user_id', 'service_category'])
            ->groupBy(fn (StructureDraft $draft): int => (int) $draft->user_id);

        $published = 0;

        PartnerProfile::query()
            ->whereIn('user_id', $awaiting->keys())
            ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
            ->orderBy('user_id')
            // Prende il posto del vecchio `canPublish()`, che per le famiglie
            // diverse dalla smartbox è la stessa regola. Un partner le cui
            // uniche bozze in attesa sono smartbox ferme di proposito non apre
            // nemmeno una transazione: AwaitingDraftPublisher le scarterebbe
            // comunque, ma le riprenderebbe una per una ogni dieci minuti.
            ->get()
            ->filter(fn (PartnerProfile $profile): bool => $awaiting
                ->get((int) $profile->user_id, collect())
                ->contains(fn (StructureDraft $draft): bool => $profile->canPublishFamily($draft->family())))
            ->each(function (PartnerProfile $profile) use ($publisher, &$published): void {
                try {
                    $published += $publisher->publishFor((int) $profile->user_id);
                } catch (Throwable $exception) {
                    Log::warning('Pubblicazione delle bozze in attesa fallita', [
                        'partner_user_id' => $profile->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                    $this->components->warn("Partner {$profile->user_id}: {$exception->getMessage()}");
                }
            });

        $this->components->info("Bozze pubblicate: {$published}.");

        return self::SUCCESS;
    }
}
