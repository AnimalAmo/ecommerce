<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
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
        // Vincolato all'utente: nessuno può modificare i servizi altrui.
        $draft = StructureDraft::completedFor(Auth::id())->firstWhere('id', $draftId);

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
        return view('livewire.partner.my-services.index', [
            'services' => StructureDraft::completedFor(Auth::id())->get(),
        ])->title(__('partner.services.title'));
    }
}
