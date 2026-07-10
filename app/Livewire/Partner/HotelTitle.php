<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelTitle extends Component
{
    use InteractsWithStructureDraft;

    /** Nome della struttura ricettiva (hotel). */
    public string $name = '';

    public function mount(): void
    {
        $this->name = $this->draft()->name ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['name' => ['required', 'string', 'max:128']],
            ['name.required' => __('partner.hotel_title.error_required')],
        );

        $this->saveStep(['name' => $this->name], 2);
        $this->redirectRoute('partner.structure.hotel.location');
    }

    public function render()
    {
        return view('livewire.partner.hotel-title')
            ->title(__('partner.hotel_title.title'));
    }
}
