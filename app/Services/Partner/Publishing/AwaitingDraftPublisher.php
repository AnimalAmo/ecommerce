<?php

namespace App\Services\Partner\Publishing;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pubblica le bozze che un partner ha chiuso quando non poteva ancora essere
 * pagato (P4). La chiamano il job PublishAwaitingDrafts, che parte da
 * account.updated e dal cambio di modalità, e il comando schedulato.
 *
 * Può girare due volte in contemporanea (due account.updated ravvicinati, o il
 * job e il comando). Per questo ogni bozza ha la sua transazione: la si
 * rilegge con lockForUpdate e si ricontrolla il segnale. La prima che
 * pubblica azzera il segnale, la seconda trova la bozza non più in attesa e
 * passa oltre. Anche un doppio passaggio non fa danni: DraftCompleter è
 * idempotente (updateOrCreate su structure_draft_id).
 *
 * Nessuna dipendenza da Auth, sessione o request: gira nel webhook, in coda e
 * da console. Per questo il controllo che il wizard fa col middleware
 * `partner` (utente attivo) qui si rifà a mano.
 */
class AwaitingDraftPublisher
{
    public function __construct(private readonly DraftCompleter $completer) {}

    /** Quante bozze in attesa del partner sono andate a catalogo. */
    public function publishFor(int $partnerId): int
    {
        $draftIds = StructureDraft::query()
            ->where('user_id', $partnerId)
            ->whereNull('merged_into_draft_id')
            ->awaitingPublication()
            ->orderBy('id')
            ->pluck('id');

        $published = 0;

        foreach ($draftIds as $draftId) {
            try {
                if ($this->publishOne((int) $draftId)) {
                    $published++;
                }
            } catch (Throwable $exception) {
                // Una bozza avvelenata non ferma le altre: le si scorre per id,
                // quindi senza questo catch il job (tries = 3) morirebbe sempre
                // sulla prima e le successive non andrebbero mai a catalogo.
                // Stessa scelta del comando, che isola i partner fra loro.
                $this->reportFailure((int) $draftId, $exception);
            }
        }

        return $published;
    }

    /**
     * I controlli su utente attivo e gate di pubblicazione vengono prima di
     * DraftCompleter: un partner ancora non pagabile altrimenti riscriverebbe
     * il segnale a ogni giro del comando, e uno disattivato (o anonimizzato)
     * finirebbe a catalogo. Una bozza diventata non pubblicabile resta in
     * attesa, così resta in "I miei servizi": la corregge il partner.
     *
     * Il gate è `canPublishFamily()`, per bozza e non per partner: una smartbox
     * di chi incassa fuori dalla piattaforma (27/09/2026) non va a catalogo
     * mentre le altre bozze dello stesso partner sì. Qui si esce con `false` e
     * in silenzio: è una riga ferma di proposito, non un errore, e il comando
     * la ritrova ogni dieci minuti finché il partner non torna al pagamento
     * online — un warning per volta sarebbe un warning al giorno per sempre.
     * Non contata fra le pubblicate, perché non è stata pubblicata.
     */
    private function publishOne(int $draftId): bool
    {
        try {
            return DB::transaction(function () use ($draftId): bool {
                $draft = StructureDraft::query()->whereKey($draftId)->lockForUpdate()->first();

                if ($draft === null || $draft->merged_into_draft_id !== null || ! $draft->isAwaitingPublication()) {
                    return false;
                }

                $owner = $draft->user;

                if ($owner === null || ! $owner->is_active) {
                    return false;
                }

                if ($owner->partnerProfile?->canPublishFamily($draft->family()) !== true) {
                    return false;
                }

                return $this->completer->complete($draft, $draft->finalStep()) === DraftCompletion::Published;
            });
        } catch (DraftNotPublishableException $exception) {
            $this->reportUnpublishable($draftId, $exception);

            return false;
        }
    }

    /**
     * Al massimo un warning al giorno per bozza: il comando la ritrova ogni
     * dieci minuti finché il partner non la corregge.
     */
    private function reportUnpublishable(int $draftId, DraftNotPublishableException $exception): void
    {
        if (! Cache::add("partner.awaiting-draft-unpublishable.{$draftId}", true, now()->addDay())) {
            return;
        }

        Log::warning('Bozza in attesa di Stripe non pubblicabile', [
            'structure_draft_id' => $draftId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Errore imprevisto su una bozza: non è il caso previsto della bozza
     * incompleta, quindi si logga ogni volta e senza sconti.
     */
    private function reportFailure(int $draftId, Throwable $exception): void
    {
        Log::warning('Pubblicazione della bozza in attesa fallita', [
            'structure_draft_id' => $draftId,
            'error' => $exception->getMessage(),
        ]);
    }
}
