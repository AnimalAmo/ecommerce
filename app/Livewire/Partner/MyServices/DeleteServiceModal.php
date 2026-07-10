<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Popup di conferma eliminazione servizio (XD "Pop-up elimina servizio").
 * Componente ad-hoc: la lista dispaccia `delete-service` con l'id, questo apre
 * il modale coi dati del servizio e, alla conferma, elimina e notifica la lista.
 */
class DeleteServiceModal extends Component
{
    public ?int $serviceId = null;

    public string $name = '';

    public string $location = '';

    public ?string $cover = null;

    #[On('delete-service')]
    public function open(int $id): void
    {
        $draft = StructureDraft::completedFor(Auth::id())->firstWhere('id', $id);
        if (! $draft) {
            return;
        }

        $this->serviceId = $draft->id;
        $this->name = (string) $draft->name;
        $this->location = $draft->locationLabel();
        $this->cover = $draft->coverPhotoUrl();

        Flux::modal('delete-service')->show();
    }

    public function delete(): void
    {
        if ($this->serviceId !== null) {
            // Vincolato all'utente: nessuno può eliminare i servizi altrui.
            StructureDraft::query()
                ->where('user_id', Auth::id())
                ->whereKey($this->serviceId)
                ->delete();
        }

        Flux::modal('delete-service')->close();
        $this->reset();
        $this->dispatch('service-deleted');
    }

    public function render()
    {
        return view('livewire.partner.my-services.delete-service-modal');
    }
}
