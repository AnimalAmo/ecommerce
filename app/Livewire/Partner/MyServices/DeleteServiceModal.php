<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $draft = StructureDraft::listableFor(Auth::id())->firstWhere('id', $id);
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
            // Senza unpublish la riga catalogo resterebbe live per sempre
            // (FK nullOnDelete: il delete del draft la orfanerebbe soltanto).
            //
            // La bozza si rilegge col lucchetto dentro la transazione perché
            // con P4 la pubblicazione arriva anche da job e comando: senza
            // serializzare, la delete leggerebbe uno stato ancora "non
            // pubblicato", il publisher committerebbe la riga a catalogo e
            // questa resterebbe viva e orfana. Col lucchetto la delete aspetta
            // e trova la riga da togliere; se arriva prima lei, è il publisher
            // a trovare la bozza sparita.
            //
            // Vincolato all'utente e a ciò che la lista mostra: nessuno elimina
            // i servizi altrui, né una bozza a metà con un serviceId riscritto.
            DB::transaction(function (): void {
                $draft = StructureDraft::listableFor(Auth::id())
                    ->lockForUpdate()
                    ->find($this->serviceId);

                if ($draft === null) {
                    return;
                }

                app(DraftPublisher::class)->unpublish($draft);
                $draft->delete();
            });
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
