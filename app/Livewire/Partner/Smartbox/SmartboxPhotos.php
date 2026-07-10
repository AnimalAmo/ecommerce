<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SmartboxPhotos extends Component
{
    use InteractsWithStructureDraft, WithFileUploads;

    /** Nuove foto in upload (temporanee Livewire). */
    public array $photos = [];

    /** Percorsi delle foto già salvate nella bozza. */
    public array $saved = [];

    public function mount(): void
    {
        $this->saved = $this->draft()->photos ?? [];
    }

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

    public function removeSaved(int $index): void
    {
        if (isset($this->saved[$index])) {
            Storage::disk('public')->delete($this->saved[$index]);
            unset($this->saved[$index]);
            $this->saved = array_values($this->saved);
            $this->draft()->update(['photos' => $this->saved]);
        }
    }

    public function next(): void
    {
        if (count($this->saved) + count($this->photos) < 4) {
            $this->addError('photos', __('partner.smartbox_photos.error_min'));

            return;
        }

        $this->validate(['photos.*' => ['image', 'max:8192']]);

        $paths = $this->saved;
        foreach ($this->photos as $photo) {
            $paths[] = $photo->store('smartbox-photos', 'public');
        }

        $this->saveStep(['photos' => $paths], 11);
        $this->redirectRoute('partner.smartbox.price');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-photos')
            ->title(__('partner.smartbox_photos.title'));
    }
}
