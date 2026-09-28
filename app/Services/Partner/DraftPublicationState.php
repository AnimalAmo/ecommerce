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
 *                         non ha fatto. Per una bozza senza riga vale solo
 *                         se la causa è la modalità di incasso
 *                         (PartnerProfile::needsOnlinePaymentFor): una
 *                         smartbox di chi è online e deve solo finire Stripe
 *                         è `awaiting_stripe` (difetto F3, 28/09/2026);
 *  - `suspended`          la scheda è a catalogo ma sospesa dal pannello;
 *  - `awaiting_approval`  a catalogo, in attesa di approvazione (moderazione accesa);
 *  - `published`          online, nessun badge;
 *  - `incomplete`         mancano i dati minimi: non andrà mai a catalogo così;
 *  - `awaiting_stripe`    pronta, ma il partner non può ancora pubblicare
 *                         finché Stripe non è collegato e pagabile;
 *  - `publishing`         pronta e il partner può pubblicare: la rete di
 *                         sicurezza schedulata la prende entro dieci minuti;
 *  - `draft`              né a catalogo né col segnale: di norma a metà
 *                         wizard. Dal difetto W2 (28/09/2026) le bozze in
 *                         corso compaiono in "I miei servizi", e la lista le
 *                         mostra con «Bozza in corso» e «Riprendi» quando
 *                         StructureDraft::isInProgress() lo conferma. Lo
 *                         stesso stato copre un servizio completato rimasto
 *                         senza riga a catalogo (caso storico): quello resta
 *                         senza badge, come prima.
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
                // La causa accanto al gate (difetto F3): senza, una smartbox
                // ferma per Stripe leggeva la stessa diagnosi di una ferma
                // per la modalità di incasso.
                $draft->id => $this->state(
                    $draft,
                    $published->get($draft->id),
                    $profile?->canPublishFamily($draft->family()) === true,
                    $profile?->needsOnlinePaymentFor($draft->family()) === true,
                ),
            ])
            ->all();
    }

    private function state(StructureDraft $draft, ?Model $row, bool $canPublishFamily, bool $needsOnlinePayment): string
    {
        // Ritirata da noi, non sospesa dall'admin: `withheld_at` lascia la riga
        // dov'è, quindi senza questo ramo — che va prima del controllo sulla
        // riga — il badge direbbe «Sospesa», accusando l'admin di una cosa che
        // non ha fatto. La causa come nel ramo senza riga (review del
        // 28/09/2026): la migrazione del 27/09 ha ritirato anche le smartbox di
        // chi incassa online ma non ha finito Stripe, e a lui va detto Stripe,
        // come dicono il dettaglio e la dashboard.
        if ($row?->isWithheld() === true) {
            return match (true) {
                $needsOnlinePayment => self::AWAITING_PAYMENT_METHOD,
                $canPublishFamily => self::PUBLISHING,
                default => self::AWAITING_STRIPE,
            };
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
        // segnalazione. Poi la causa del blocco, come la sceglie DraftPublisher.
        // Difetto F3 (28/09/2026): qui bastava che la bozza fosse una smartbox
        // non pubblicabile per dire «Serve il sistema di pagamento», anche a
        // chi incassa già online e deve solo finire Stripe — e la dashboard,
        // con la stessa regola, gli mostrava due banner per un fatto solo.
        // Ora il badge del pagamento è di chi incassa in struttura; gli altri
        // leggono l'attesa di Stripe, come una struttura o un'attività.
        return match (true) {
            ! $this->isPublishable($draft) => self::INCOMPLETE,
            $canPublishFamily => self::PUBLISHING,
            $needsOnlinePayment => self::AWAITING_PAYMENT_METHOD,
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
            // Data obbligatoria solo per gli eventi: un'attività o un servizio
            // professionale (dog sitter, toelettatore) può non averne una
            // (risposta della cliente, 27/09/2026), e pretenderla lo teneva
            // fuori dal catalogo per sempre.
            'attivita' => $draft->type !== 'eventi' || $draft->date_start !== null,
            'smartbox' => filled($draft->price),
            default => filled($draft->rooms),
        };
    }
}
