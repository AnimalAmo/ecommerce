<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\DraftPublicationState;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PartnerMyServices extends Component
{
    /** Ricarica la lista dopo un'eliminazione dal modale ad-hoc. */
    #[On('service-deleted')]
    public function refreshList(): void
    {
        // Il solo handling dell'evento forza il re-render con la lista aggiornata.
    }

    /**
     * Modifica B2B (XD "Modifica B2B – …"): riporta il partner nel form a
     * step della famiglia del servizio, con il draft caricato — gli step
     * idratano i campi dal draft di sessione e il completamento finale
     * ri-pubblica la stessa riga catalogo (structure_draft_id unique).
     */
    public function edit(int $draftId): void
    {
        // Vincolato all'utente: nessuno può modificare i servizi altrui. Si
        // aprono anche le bozze in attesa di Stripe: il partner le può
        // correggere, e l'ultimo step le rimette in attesa o le pubblica.
        // E le bozze in corso, dal primo step: per ripartire da dove si era
        // rimasti c'è resume().
        $draft = StructureDraft::listableFor(Auth::id())->firstWhere('id', $draftId);

        if ($draft === null) {
            return;
        }

        session(['structure_draft_id' => $draft->id]);

        $this->redirectRoute($draft->wizardRoute(1));
    }

    /**
     * «Riprendi» di una bozza in corso (difetto W2): rimette la sessione sulla
     * bozza — è la sessione che ogni step legge — e porta il partner al primo
     * step che non ha ancora salvato, non all'inizio del wizard. Quello che ha
     * già compilato lo ritrova negli step precedenti, che si idratano dalla bozza.
     */
    public function resume(int $draftId): void
    {
        // Stessa scope della lista: solo bozze proprie e visibili qui. Le altre
        // (servizi completati, in attesa) si aprono con edit().
        $draft = StructureDraft::listableFor(Auth::id())->firstWhere('id', $draftId);

        if ($draft === null || ! $draft->isInProgress()) {
            return;
        }

        session(['structure_draft_id' => $draft->id]);

        $this->redirectRoute($draft->resumeRoute());
    }

    public function render()
    {
        // Completati, in attesa di Stripe e bozze in corso (badge in vista):
        // prima una bozza chiusa senza Stripe, o lasciata a metà wizard,
        // spariva da qui e restava solo in sessione.
        $services = StructureDraft::listableFor(Auth::id())->get();

        return view('livewire.partner.my-services.index', [
            'services' => $services,
            'states' => app(DraftPublicationState::class)->forDrafts($services, Auth::user()),
        ])->title(__('partner.services.title'));
    }
}
