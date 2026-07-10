<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\HandlesPhotoUploads;
use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityPhotos extends Component
{
    use HandlesPhotoUploads, InteractsWithStructureDraft;

    public function next(): void
    {
        $paths = $this->collectPhotos();
        if ($paths === null) {
            return;
        }

        $this->saveStep(['photos' => $paths], 9);
        $this->redirectRoute('partner.activity.cancellation');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-photos')
            ->title(__('partner.activity_photos.title'));
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
