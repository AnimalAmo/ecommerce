<?php

namespace App\Services\Partner\Publishing;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Exceptions\PartnerNotPayableException;
use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\DB;

/**
 * Chiude una bozza in un posto solo: il wizard partner oggi, il pannello admin
 * con P3 (spec §5.2). Prima la chiusura stava in InteractsWithStructureDraft e
 * ignorava il ritorno di DraftPublisher::publish: una bozza incompleta finiva
 * `completed` in "I miei servizi" senza nessuna riga a catalogo.
 *
 * Esiti:
 * - pubblicata: `completed`, step finale, segnale azzerato;
 * - partner online non ancora pagabile: la transazione si annulla e, fuori di
 *   lì, si scrivono il segnale `publish_requested_at` e lo step finale. Lo
 *   stato resta quello che era: `draft` per una prima pubblicazione,
 *   `completed` per la modifica di un servizio già a catalogo (la riga resta
 *   quella di prima finché Stripe non è collegato);
 * - bozza non pubblicabile: DraftNotPublishableException, nulla cambia.
 *
 * Il lavoro si fa su una copia letta con lockForUpdate, non sull'istanza del
 * chiamante. Dopo un rollback quell'istanza avrebbe ancora `completed` in
 * memoria, e un update successivo scriverebbe solo i campi "sporchi", cioè
 * nessuno di quelli che contano. L'istanza del chiamante si rilegge alla fine.
 *
 * Il segnale si scrive dopo il rollback, senza lucchetto. Se Stripe diventa
 * pagabile proprio in quella finestra, il job PublishAwaitingDrafts non trova
 * ancora il segnale e salta la bozza: la recupera entro dieci minuti
 * `animalamo:publish-awaiting-drafts`.
 *
 * Idempotente: i publisher fanno updateOrCreate su structure_draft_id, quindi
 * AwaitingDraftPublisher la può richiamare su una bozza già pubblicata.
 */
class DraftCompleter
{
    public function __construct(private readonly DraftPublisher $publisher) {}

    /**
     * @throws DraftNotPublishableException mancano i dati minimi per il catalogo
     */
    public function complete(StructureDraft $draft, int $finalStep): DraftCompletion
    {
        try {
            DB::transaction(function () use ($draft, $finalStep): void {
                $locked = StructureDraft::query()->whereKey($draft->getKey())->lockForUpdate()->firstOrFail();

                $locked->update([
                    'status' => StructureDraft::STATUS_COMPLETED,
                    'current_step' => $finalStep,
                    'publish_requested_at' => null,
                ]);

                if ($this->publisher->publish($locked) === null) {
                    throw DraftNotPublishableException::forDraft($locked);
                }
            });
        } catch (PartnerNotPayableException) {
            // Fuori dalla transazione annullata, con una query diretta: lo
            // status non si tocca, il segnale sì.
            StructureDraft::query()->whereKey($draft->getKey())->update([
                'publish_requested_at' => now(),
                'current_step' => $finalStep,
            ]);

            $draft->refresh();

            return DraftCompletion::AwaitingPayout;
        }

        $draft->refresh();

        return DraftCompletion::Published;
    }
}
