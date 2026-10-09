<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ManagesRoomRows;
use App\Livewire\Forms\HotelRoomsForm;
use App\Services\Partner\ServiceOptionLabels;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Step 5 «Stanze»: card per stanza, modifica in modale (ManagesRoomRows).
 * Ogni stanza salvata dalla modale va subito nella bozza, foto comprese:
 * «Avanti» valida l'insieme, gli orari e avanza lo step.
 */
class HotelRooms extends Component
{
    use InteractsWithStructureDraft, ManagesRoomRows, WithFileUploads;

    public HotelRoomsForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        // Le stanze sono quelle della bozza (scritte dalla modale), non il
        // payload: il client può riscrivere `form.rooms`, chiavi e foto comprese.
        $this->form->rooms = $this->baseRoomRows();
        $this->form->validate();

        $this->saveStep($this->form->toDraft(), 5);
        $this->redirectRoute('partner.structure.hotel.cancellation');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-rooms', [
            'roomTypes' => ServiceOptionLabels::options('room_type'),
            'roomAmenities' => ServiceOptionLabels::roomAmenityOptions(),
        ])->title(__('partner.hotel_rooms.title'));
    }

    protected function roomsForm(): HotelRoomsForm
    {
        return $this->form;
    }

    /**
     * Le stanze della bozza. L'alloggio intero senza ancora una riga parte
     * dalla card vuota del form (la chiave nuova la riscrive saveRoom).
     */
    protected function baseRoomRows(): array
    {
        $rows = $this->draft()->normalizedRooms();

        if ($rows === [] && $this->form->wholeProperty) {
            return [$this->form->blankRow()];
        }

        return $rows;
    }

    /** Le foto delle stanze così come sono nella bozza: la fonte di verità, non il payload. */
    protected function ownedRoomPhotos(?string $key): array
    {
        return collect($this->draft()->rooms ?? [])
            ->pluck('photos')
            ->flatten()
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();
    }

    /** Stesso disco e cartella dello step foto (HotelPhotos). */
    protected function acceptRoomUploads(string $key, array $files): array
    {
        return array_values(array_map(fn ($file): string => $file->store('structure-photos', 'public'), $files));
    }

    /**
     * Le stanze vanno subito nella bozza, senza toccare `current_step`: le foto
     * caricate non si perdono se il partner esce senza «Avanti», e la bozza è
     * la fonte da cui si decide quali foto una stanza possiede.
     *
     * Conseguenza accettata: su una bozza in attesa di Stripe, una
     * pubblicazione automatica (AwaitingDraftPublisher, al collegamento di
     * Stripe o al cambio di modalità di pagamento) prende le stanze così come
     * sono in quel momento, anche prima di «Avanti». Ogni riga scritta qui è
     * però una riga validata dalla modale (roomRules), mai il payload; mancano
     * solo i controlli d'insieme (almeno una stanza, orari), che una bozza in
     * modifica aveva già superato.
     */
    protected function persistRooms(array $rows): void
    {
        $this->draft()->update(['rooms' => $rows]);
    }
}
