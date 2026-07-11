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

    public function render()
    {
        return view('livewire.partner.my-services.index', [
            'services' => StructureDraft::completedFor(Auth::id())->get(),
        ])->title(__('partner.services.title'));
    }
}
