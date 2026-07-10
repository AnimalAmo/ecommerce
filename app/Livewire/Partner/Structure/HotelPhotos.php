<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\HandlesPhotoUploads;
use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelPhotos extends Component
{
    use HandlesPhotoUploads, InteractsWithStructureDraft;

    public function next(): void
    {
        $paths = $this->collectPhotos();
        if ($paths === null) {
            return;
        }

        $this->saveStep(['photos' => $paths], 10);
        $this->redirectRoute('partner.structure.hotel.payment');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-photos')
            ->title(__('partner.hotel_photos.title'));
    }

    protected function photoDirectory(): string
    {
        return 'structure-photos';
    }

    protected function photoMinError(): string
    {
        return __('partner.hotel_photos.error_min');
    }
}
