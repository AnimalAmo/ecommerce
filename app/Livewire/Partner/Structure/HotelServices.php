<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\HotelServicesForm;
use Livewire\Component;

class HotelServices extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    public HotelServicesForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 7);
        $this->redirectRoute('partner.structure.hotel.animal-services');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-services', ['times' => $this->times()])
            ->title(__('partner.hotel_services.title'));
    }
}
