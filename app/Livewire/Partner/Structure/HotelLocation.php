<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Forms\HotelLocationForm;
use App\Models\Region\Province;
use Livewire\Component;

class HotelLocation extends Component
{
    use InteractsWithStructureDraft;

    public HotelLocationForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 3);
        $this->redirectRoute('partner.structure.hotel.description');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-location', [
            'provinces' => Province::orderBy('name')->get(),
        ])->title(__('partner.hotel_location.title'));
    }
}
