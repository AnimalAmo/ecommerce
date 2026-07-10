<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * Logica condivisa dello step "foto" del wizard partner (hotel / attività /
 * smartbox): tiene separate le foto già salvate nella bozza da quelle in
 * upload, gestisce rimozione e validazione, e persiste i file su disco.
 *
 * Il componente che lo usa deve anche usare {@see InteractsWithStructureDraft}
 * (per `draft()` / `saveStep()`) e definire `photoDirectory()` + `photoMinError()`.
 */
trait HandlesPhotoUploads
{
    use WithFileUploads;

    /** Nuove foto in upload (temporanee Livewire). */
    public array $photos = [];

    /** Percorsi delle foto già salvate nella bozza. */
    public array $saved = [];

    /** Numero minimo di foto richiesto per proseguire. */
    protected int $minPhotos = 4;

    public function mountHandlesPhotoUploads(): void
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

    /**
     * Valida e memorizza le foto, restituendo i percorsi salvati + nuovi;
     * restituisce null (aggiungendo l'errore) se il minimo non è raggiunto.
     */
    protected function collectPhotos(): ?array
    {
        if (count($this->saved) + count($this->photos) < $this->minPhotos) {
            $this->addError('photos', $this->photoMinError());

            return null;
        }

        $this->validate(['photos.*' => ['image', 'max:8192']]);

        $paths = $this->saved;
        foreach ($this->photos as $photo) {
            $paths[] = $photo->store($this->photoDirectory(), 'public');
        }

        return $paths;
    }

    /** Cartella (disco public) in cui memorizzare le foto del flusso. */
    abstract protected function photoDirectory(): string;

    /** Messaggio d'errore quando non si raggiunge il minimo di foto. */
    abstract protected function photoMinError(): string;
}
