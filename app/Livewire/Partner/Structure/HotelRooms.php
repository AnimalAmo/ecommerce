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
        $this->form->validate();

        $attributes = $this->form->toDraft();
        // Le foto di ogni riga solo fra quelle che la bozza possiede già.
        $owned = $this->ownedRoomPhotos(null);
        foreach ($attributes['rooms'] as $i => $row) {
            $attributes['rooms'][$i]['photos'] = array_values(array_intersect($row['photos'], $owned));
        }

        $this->saveStep($attributes, 5);
        $this->redirectRoute('partner.structure.hotel.cancellation');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-rooms', [
            'roomTypes' => ServiceOptionLabels::options('room_type'),
            'roomAmenities' => ServiceOptionLabels::options('services'),
        ])->title(__('partner.hotel_rooms.title'));
    }

    protected function roomsForm(): HotelRoomsForm
    {
        return $this->form;
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
     * caricate non si perdono se il partner esce senza «Avanti».
     */
    protected function persistRooms(array $rows): void
    {
        $this->draft()->update(['rooms' => $rows]);
    }
}
