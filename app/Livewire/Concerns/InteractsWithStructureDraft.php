<?php

namespace App\Livewire\Concerns;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftCompleter;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;

/**
 * Condivide la bozza di un servizio partner tra gli step del wizard. La bozza
 * è tracciata in sessione e ogni step salva i suoi campi e aggiorna
 * `current_step`, così lo stato parziale sopravvive se il partner si ferma.
 * `completeDraft()` la chiude all'ultimo step (hotel 11, attività 11 dopo
 * uno step 10, smartbox 12) passando da DraftCompleter.
 *
 * Il wizard è dietro ['auth','partner']: una bozza appartiene a chi la sta
 * compilando. Una bozza in sessione di un altro utente (sessione riusata,
 * impersonazione, bozze da ospite di prima dell'08/09/2026) non si apre:
 * se ne crea una nuova e resta un warning nel log.
 */
trait InteractsWithStructureDraft
{
    /**
     * Locked: è l'id della riga che ogni step scrive. Se il client potesse
     * riscriverlo, un partner compilerebbe (e pubblicherebbe) la bozza di un altro.
     */
    #[Locked]
    public ?int $draftId = null;

    /** Bozza corrente del partner loggato, dalla sessione, creandola se non esiste. */
    protected function draft(): StructureDraft
    {
        if ($this->draftId && ($draft = $this->ownedDraft($this->draftId))) {
            return $draft;
        }

        $sessionId = session('structure_draft_id');

        if ($sessionId) {
            if ($draft = $this->ownedDraft((int) $sessionId)) {
                $this->draftId = $draft->id;

                return $draft;
            }

            session()->forget('structure_draft_id');
        }

        $draft = StructureDraft::create([
            'user_id' => Auth::id(),
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 0,
        ]);
        session(['structure_draft_id' => $draft->id]);
        $this->draftId = $draft->id;

        return $draft;
    }

    /** Salva i campi dello step e avanza `current_step` (mai indietro). */
    protected function saveStep(array $attributes, int $step): StructureDraft
    {
        $draft = $this->draft();
        $draft->fill($attributes);

        if ($step > $draft->current_step) {
            $draft->current_step = $step;
        }

        $draft->save();

        return $draft;
    }

    /**
     * "Indietro" del primo step di ogni famiglia. Chi sta modificando un
     * servizio di "I miei servizi" (completato o in attesa di Stripe) torna
     * alla lista: "Crea servizio" gli aprirebbe una bozza nuova e la modifica
     * si perderebbe in silenzio.
     *
     * Una bozza nuova, a qualunque step, torna alle card SENZA parametri: la
     * rotta nuda riprende la bozza in corso della sessione (CreateService::mount).
     * Fino al difetto W2 la stessa rotta la scollegava e ne apriva un'altra,
     * così questo pulsante orfanava il lavoro fatto. `?nuovo=1` è solo del
     * link «Crea servizio» dell'header, che chiede un servizio nuovo.
     */
    protected function serviceChoiceBackUrl(): string
    {
        $draft = $this->draft();

        return $draft->status === StructureDraft::STATUS_COMPLETED || $draft->isAwaitingPublication()
            ? route('partner.services')
            : route('partner.service.create');
    }

    /**
     * Chiude la bozza all'ultimo step (default: lo step finale della sua
     * famiglia). Pubblicata o in attesa, il wizard è finito: la
     * sessione si libera, così "Crea servizio" non la riprende e il servizio
     * successivo parte da una bozza nuova, anche dalla rotta senza parametri. Nel secondo
     * caso l'avviso va in un flash che la dashboard mostra, perché un toast
     * lanciato prima del redirect si perde. L'avviso dice la causa (Stripe da
     * finire, o incasso online per una smartbox: awaitingNotice) e distingue
     * un servizio nuovo dalla modifica di uno già completato, la cui versione
     * precedente resta quella pubblicata. Se invece mancano i dati minimi, il partner
     * resta sullo step col toast e la sessione resta sulla bozza, per correggerla.
     *
     * @return bool true quando il chiamante deve andare in dashboard
     */
    protected function completeDraft(?int $finalStep = null): bool
    {
        $draft = $this->draft();
        $wasCompleted = $draft->status === StructureDraft::STATUS_COMPLETED;

        try {
            $outcome = app(DraftCompleter::class)->complete($draft, $finalStep ?? $draft->finalStep());
        } catch (DraftNotPublishableException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return false;
        }

        session()->forget('structure_draft_id');

        if ($outcome === DraftCompletion::AwaitingPayout) {
            session()->flash('partner.notice', $this->awaitingNotice($draft, $wasCompleted));
        }

        return true;
    }

    /**
     * L'avviso di una bozza chiusa ma ferma, scelto sulla causa del blocco.
     *
     * Difetto F2 dell'audit dei flussi (28/09/2026): DraftCompleter torna
     * `AwaitingPayout` per due cause diverse, e qui si guardava solo
     * `$wasCompleted`. Alla smartbox di chi incassa in struttura arrivava
     * «completa il collegamento del conto su Stripe»: il partner lo collegava
     * e la smartbox restava ferma, perché le serve l'incasso online. La causa
     * si legge dal profilo con la stessa regola che DraftPublisher usa per
     * scegliere l'eccezione, nella stessa richiesta e subito dopo: le due
     * letture divergono solo se il profilo cambia in mezzo, e allora l'avviso
     * segue il profilo più recente, che è quello che il partner vedrà.
     */
    private function awaitingNotice(StructureDraft $draft, bool $wasCompleted): string
    {
        $needsOnlinePayment = $draft->user?->partnerProfile?->needsOnlinePaymentFor($draft->family()) === true;

        return match (true) {
            $needsOnlinePayment && $wasCompleted => __('partner.publish.awaiting_payment_method_changes'),
            $needsOnlinePayment => __('partner.publish.awaiting_payment_method'),
            $wasCompleted => __('partner.publish.awaiting_stripe_changes'),
            default => __('partner.publish.awaiting_stripe'),
        };
    }

    /**
     * La bozza, se è di chi è loggato. Senza proprietario vale solo per un
     * ospite (i test degli step lo sono), mai per un partner loggato.
     */
    private function ownedDraft(int $id): ?StructureDraft
    {
        $draft = StructureDraft::find($id);

        if ($draft === null) {
            return null;
        }

        $owner = $draft->user_id === null ? null : (int) $draft->user_id;
        $current = Auth::id() === null ? null : (int) Auth::id();

        if ($owner === $current) {
            return $draft;
        }

        Log::warning('Bozza di un altro utente ignorata dal wizard partner', [
            'structure_draft_id' => $draft->id,
            'draft_user_id' => $owner,
            'user_id' => $current,
        ]);

        return null;
    }
}
