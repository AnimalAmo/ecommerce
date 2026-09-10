<?php

namespace App\Livewire\Concerns;

use App\Exceptions\PartnerNotPayableException;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Condivide la bozza di onboarding struttura tra i vari step del wizard partner.
 * La bozza è tracciata in sessione (non c'è ancora l'auth partner) e ogni step
 * salva i suoi campi + aggiorna `current_step`, così lo stato parziale sopravvive
 * se l'utente interrompe. `completeDraft($finalStep)` la chiude all'ultimo step
 * del flusso (hotel 11, attività 10, smartbox 12).
 */
trait InteractsWithStructureDraft
{
    public ?int $draftId = null;

    /** Bozza corrente dalla sessione, creandola se non esiste. */
    protected function draft(): StructureDraft
    {
        if ($this->draftId && ($draft = StructureDraft::find($this->draftId))) {
            return $draft;
        }

        $sessionId = session('structure_draft_id');
        if ($sessionId && ($draft = StructureDraft::find($sessionId))) {
            $this->draftId = $draft->id;

            return $draft;
        }

        // Un partner loggato "possiede" i servizi che crea (anche se il wizard è
        // pubblico): così compaiono nella sua pagina "I miei servizi".
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
     * Chiude la bozza all'ultimo step del flusso (default 11), la pubblica sul
     * catalogo B2C e libera la sessione. MVP: pubblicazione automatica — la
     * moderazione superadmin prevista dalla spec arriverà come gate a monte
     * (vedi DraftPublisher).
     */
    protected function completeDraft(int $finalStep = 11): void
    {
        $draft = $this->draft();

        // Transazione: se la pubblicazione fallisce, lo status torna draft e il
        // partner può riprovare (niente bozze "completed" mai arrivate a catalogo).
        try {
            DB::transaction(function () use ($draft, $finalStep): void {
                $draft->update([
                    'status' => StructureDraft::STATUS_COMPLETED,
                    'current_step' => $finalStep,
                ]);

                app(DraftPublisher::class)->publish($draft->fresh());
            });
        } catch (PartnerNotPayableException $exception) {
            // Onboarding Stripe incompleto: la bozza resta aperta e il partner
            // sa perché, invece di vedere un wizard concluso e nessun servizio.
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        session()->forget('structure_draft_id');
    }
}
