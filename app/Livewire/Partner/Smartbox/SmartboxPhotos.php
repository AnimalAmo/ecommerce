<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\HandlesPhotoUploads;
use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxPhotos extends Component
{
    use HandlesPhotoUploads, InteractsWithStructureDraft;

    public function next(): void
    {
        $paths = $this->collectPhotos();
        if ($paths === null) {
            return;
        }

        $this->saveStep(['photos' => $paths], 11);
        $this->redirectRoute('partner.smartbox.price');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-photos')
            ->title(__('partner.smartbox_photos.title'));
    }

    protected function photoDirectory(): string
    {
        return 'smartbox-photos';
    }

    protected function photoMinError(): string
    {
        return __('partner.smartbox_photos.error_min');
    }
}
