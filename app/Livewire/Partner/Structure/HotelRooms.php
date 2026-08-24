<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Forms\HotelRoomsForm;
use Livewire\Component;

class HotelRooms extends Component
{
    use InteractsWithStructureDraft;

    public HotelRoomsForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    /**
     * I tre controlli del repeater sono inerti sulla casa vacanza: l'alloggio
     * è uno solo. Il guard sta qui e non solo nel blade perché i pulsanti
     * spariscono dalla vista ma le action restano richiamabili dal client.
     */
    public function addRoom(): void
    {
        if ($this->form->wholeProperty) {
            return;
        }

        $this->form->rooms[] = $this->form->blankRow();
    }

    public function incrementRoom(int $i): void
    {
        if ($this->form->wholeProperty) {
            return;
        }

        if (isset($this->form->rooms[$i])) {
            $this->form->rooms[$i]['count']++;
        }
    }

    public function decrementRoom(int $i): void
    {
        if ($this->form->wholeProperty) {
            return;
        }

        if (isset($this->form->rooms[$i]) && $this->form->rooms[$i]['count'] > 0) {
            $this->form->rooms[$i]['count']--;
        }
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 5);
        $this->redirectRoute('partner.structure.hotel.cancellation');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-rooms')
            ->title(__('partner.hotel_rooms.title'));
    }
}
