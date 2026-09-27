<?php

namespace App\Services\Partner;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Perché un servizio non è (ancora) visibile sul sito.
 *
 * Nasce dalla segnalazione della cliente del 29/09/2026: «alcune strutture
 * hanno collegato Stripe ma la scheda non viene pubblicata». Parte del
 * problema era la diagnosi, non la pubblicazione — "I miei servizi" mostrava
 * `awaiting_stripe` su OGNI bozza ferma, senza guardare lo stato di Stripe. Chi
 * era fermo per un altro motivo (dati mancanti, moderazione, cron non attivo)
 * leggeva «in attesa del collegamento Stripe», ci credeva, e apriva un ticket.
 *
 * Gli stati, in ordine di precedenza:
 *  - `awaiting_payment_method` smartbox ritirata (o ferma) perché il partner
 *                         non incassa online: richiesta della cliente del
 *                         27/09/2026. Viene prima di tutto perché la riga a
 *                         catalogo c'è, marcata `withheld_at`, e senza questo
 *                         stato leggerebbe «Sospesa» — una cosa che l'admin
 *                         non ha fatto;
 *  - `suspended`          la scheda è a catalogo ma sospesa dal pannello;
 *  - `awaiting_approval`  a catalogo, in attesa di approvazione (moderazione accesa);
 *  - `published`          online, nessun badge;
 *  - `incomplete`         mancano i dati minimi: non andrà mai a catalogo così;
 *  - `awaiting_stripe`    pronta, ma il partner non può ancora pubblicare;
 *  - `publishing`         pronta e il partner può pubblicare: la rete di
 *                         sicurezza schedulata la prende entro dieci minuti;
 *  - `draft`              a metà wizard, nessun badge.
 */
class DraftPublicationState
{
    public const AWAITING_PAYMENT_METHOD = 'awaiting_payment_method';

    public const SUSPENDED = 'suspended';

    public const AWAITING_APPROVAL = 'awaiting_approval';

    public const PUBLISHED = 'published';

    public const INCOMPLETE = 'incomplete';

    public const AWAITING_STRIPE = 'awaiting_stripe';

    public const PUBLISHING = 'publishing';

    public const DRAFT = 'draft';

    /**
     * Stato per id di bozza. Le righe a catalogo si leggono in tre query (una
     * per famiglia), non una per bozza.
     *
     * @param  EloquentCollection<int, StructureDraft>  $drafts
     * @return array<int, string>
     */
    public function forDrafts(EloquentCollection $drafts, ?User $owner): array
    {
        if ($drafts->isEmpty()) {
            return [];
        }

        $published = $this->publishedRows($drafts);
        $profile = $owner?->partnerProfile;

        return $drafts
            ->mapWithKeys(fn (StructureDraft $draft): array => [
                // Il gate si calcola per bozza: dal 27/09/2026 dipende dalla
                // famiglia, e un solo bool per tutte direbbe il falso su metà.
                $draft->id => $this->state(
                    $draft,
                    $published->get($draft->id),
                    $profile?->canPublishFamily($draft->family()) === true,
                ),
            ])
            ->all();
    }

    private function state(StructureDraft $draft, ?Model $row, bool $canPublishFamily): string
    {
        // Ritirata da noi, non sospesa dall'admin: `withheld_at` lascia la riga
        // dov'è, quindi senza questo ramo — che va prima del controllo sulla
        // riga — il badge direbbe «Sospesa», accusando l'admin di una cosa che
        // non ha fatto. Copre anche la finestra fra il ritorno al pagamento
        // online e la ripubblicazione del cron, che dura fino a dieci minuti.
        if ($row?->isWithheld() === true) {
            return self::AWAITING_PAYMENT_METHOD;
        }

        if ($row !== null) {
            return match (true) {
                $row->isSuspended() => self::SUSPENDED,
                ! $row->isApproved() => self::AWAITING_APPROVAL,
                default => self::PUBLISHED,
            };
        }

        if (! $draft->isAwaitingPublication()) {
            return self::DRAFT;
        }

        // Prima i dati, poi il pagamento: dire "in attesa di Stripe" a chi ha
        // una bozza incompleta è la diagnosi falsa che ha generato la
        // segnalazione. Per la smartbox il motivo non è l'onboarding a metà ma
        // il sistema di pagamento, e il badge lo dice con parole sue.
        return match (true) {
            ! $this->isPublishable($draft) => self::INCOMPLETE,
            ! $canPublishFamily && $draft->family() === 'smartbox' => self::AWAITING_PAYMENT_METHOD,
            $canPublishFamily => self::PUBLISHING,
            default => self::AWAITING_STRIPE,
        };
    }

    /**
     * Righe di catalogo delle bozze, per id di bozza. `withHidden`: servono
     * proprio quelle che il sito non mostra.
     *
     * @param  EloquentCollection<int, StructureDraft>  $drafts
     * @return Collection<int, Model>
     */
    private function publishedRows(EloquentCollection $drafts): Collection
    {
        $byFamily = $drafts->groupBy(fn (StructureDraft $draft): string => $draft->family());

        $models = [
            'attivita' => Event::class,
            'smartbox' => SmartboxPackage::class,
            'struttura' => Structure::class,
        ];

        return collect($models)
            ->flatMap(function (string $model, string $family) use ($byFamily): Collection {
                $ids = $byFamily->get($family)?->modelKeys() ?? [];

                return $ids === []
                    ? collect()
                    : $model::withHidden()->whereIn('structure_draft_id', $ids)->get();
            })
            ->keyBy('structure_draft_id');
    }

    /**
     * Gli stessi requisiti minimi di DraftPublisher::isPublishable(), che è
     * privato. Se quella regola cambia, va cambiata anche qui: il badge mente
     * se le due divergono.
     */
    private function isPublishable(StructureDraft $draft): bool
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
