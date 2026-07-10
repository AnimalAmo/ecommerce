<?php

namespace App\Livewire\Partner;

use Livewire\Component;
use Livewire\WithFileUploads;

class HotelPhotos extends Component
{
    use WithFileUploads;

    /** Foto caricate (upload temporanei Livewire). */
    public array $photos = [];

    public function updatedPhotos(): void
    {
        $this->validate(['photos.*' => ['image', 'max:8192']]);
    }

    public function removePhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function next(): void
    {
        $this->validate(
            ['photos' => ['array', 'min:4']],
            ['photos.min' => __('partner.hotel_photos.error_min')],
        );

        // TODO: advance to step 11 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-photos')
            ->title(__('partner.hotel_photos.title'));
    }
}
