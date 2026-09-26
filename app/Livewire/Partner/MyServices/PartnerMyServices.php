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
        $draft = StructureDraft::listableFor(Auth::id())->firstWhere('id', $draftId);

        if ($draft === null) {
            return;
        }

        session(['structure_draft_id' => $draft->id]);

        $this->redirectRoute(match ($draft->family()) {
            'attivita' => 'partner.activity.type',
            'smartbox' => 'partner.smartbox.type',
            default => 'partner.structure.type',
        });
    }

    public function render()
    {
        // Completati e in attesa di Stripe (badge in vista): prima una bozza
        // chiusa senza Stripe spariva da qui e restava solo in sessione.
        $services = StructureDraft::listableFor(Auth::id())->get();

        return view('livewire.partner.my-services.index', [
            'services' => $services,
            'states' => app(DraftPublicationState::class)->forDrafts($services, Auth::user()),
        ])->title(__('partner.services.title'));
    }
}
