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

    public function addRoom(): void
    {
        $this->form->rooms[] = ['type' => '', 'count' => 0, 'price' => ''];
    }

    public function incrementRoom(int $i): void
    {
        if (isset($this->form->rooms[$i])) {
            $this->form->rooms[$i]['count']++;
        }
    }

    public function decrementRoom(int $i): void
    {
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
