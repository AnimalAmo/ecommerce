<?php

namespace App\Livewire\Admin\Catalog;

use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPhotoEditor;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Foto della scheda, dentro CatalogShow: un componente a parte, con il suo
 * «Salva foto», così gli upload non si mescolano al form dei testi. Le regole
 * stanno in CatalogPhotoEditor; qui c'è solo l'ordine scelto a schermo.
 *
 * L'ordine è una lista di gettoni: `saved:<path>` per una foto della scheda,
 * `new:<n>` per un upload in `$pending`. Il primo è la copertina. I path dei
 * gettoni arrivano dal client: li filtra il service contro la scheda.
 */
class CatalogPhotos extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $type = '';

    #[Locked]
    public int $itemId = 0;

    /** @var list<string> */
    public array $order = [];

    /** Bersaglio del file-upload: si svuota a ogni scelta, i file passano in `$pending`. */
    public array $uploads = [];

    /**
     * Locked: i file in coda li mette solo updatedUploads(), dopo la
     * validazione. Dal client arriverebbe un nome di file temporaneo qualsiasi.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    #[Locked]
    public array $pending = [];

    #[Locked]
    public int $nextUpload = 0;

    public function mount(string $type, int $itemId, CatalogAdmin $catalog, CatalogPhotoEditor $editor): void
    {
        $this->type = $type;
        $this->itemId = $itemId;
        $this->resetFrom($this->item($catalog), $editor);
    }

    public function updatedUploads(): void
    {
        // Un file rifiutato non deve restare in coda: la scelta successiva gli si
        // sommerebbe, e l'errore resterebbe appeso a un file che non si vede.
        try {
            $this->validateUploads();
        } catch (ValidationException $e) {
            $this->uploads = [];

            throw $e;
        }

        foreach ($this->uploads as $upload) {
            $this->pending[$this->nextUpload] = $upload;
            $this->order[] = 'new:'.$this->nextUpload++;
        }

        $this->uploads = [];
    }

    private function validateUploads(): void
    {
        $this->validate(
            ['uploads.*' => ['image', 'max:8192']],
            [
                'uploads.*.image' => __('admin-catalog.create.validation.photo_image'),
                'uploads.*.max' => __('admin-catalog.create.validation.photo_max'),
            ],
        );
    }

    public function makeCover(int $index): void
    {
        if (isset($this->order[$index])) {
            $token = $this->order[$index];
            array_splice($this->order, $index, 1);
            array_unshift($this->order, $token);
        }
    }

    public function move(int $index, int $step): void
    {
        $target = $index + ($step < 0 ? -1 : 1);

        if (isset($this->order[$index], $this->order[$target])) {
            [$this->order[$index], $this->order[$target]] = [$this->order[$target], $this->order[$index]];
        }
    }

    public function remove(int $index): void
    {
        $token = $this->order[$index] ?? null;

        if ($token === null) {
            return;
        }

        if (str_starts_with($token, 'new:')) {
            unset($this->pending[(int) substr($token, 4)]);
        }

        array_splice($this->order, $index, 1);
    }

    public function save(CatalogAdmin $catalog, CatalogPhotoEditor $editor): void
    {
        $item = $this->item($catalog);

        $photos = array_values(array_filter(array_map(fn (string $token) => match (true) {
            str_starts_with($token, 'saved:') => substr($token, 6),
            str_starts_with($token, 'new:') => $this->pending[(int) substr($token, 4)] ?? null,
            default => null,
        }, $this->order)));

        try {
            $editor->save($item, $photos);
        } catch (ValidationException $e) {
            $this->addError('photos', $e->validator->errors()->first('photos'));

            return;
        }

        $this->resetFrom($item, $editor);
        $this->dispatch('catalog-photos-saved');
        Flux::toast(text: __('admin-catalog.show.photos.saved'), variant: 'success');
    }

    public function cancel(CatalogAdmin $catalog, CatalogPhotoEditor $editor): void
    {
        $this->resetFrom($this->item($catalog), $editor);
    }

    public function render(CatalogAdmin $catalog, CatalogPhotoEditor $editor)
    {
        $item = $this->item($catalog);
        $current = $editor->current($item);

        return view('livewire.admin.catalog.photos', [
            'photos' => array_map(fn (string $token): array => [
                'token' => $token,
                'url' => $this->url($token),
                'new' => str_starts_with($token, 'new:'),
            ], $this->order),
            'dirty' => $this->order !== array_map(fn (string $path): string => 'saved:'.$path, $current),
            'partnerChanges' => $editor->hasPendingPartnerChanges($item),
            'min' => CatalogPhotoEditor::MIN_PHOTOS,
        ]);
    }

    private function resetFrom(Model $item, CatalogPhotoEditor $editor): void
    {
        $this->order = array_map(fn (string $path): string => 'saved:'.$path, $editor->current($item));
        $this->pending = [];
        $this->uploads = [];
        $this->resetErrorBag();
    }

    /** Come HasCatalogImages: path di upload → disco public, stem del template XD → asset. */
    private function url(string $token): ?string
    {
        if (str_starts_with($token, 'new:')) {
            $upload = $this->pending[(int) substr($token, 4)] ?? null;

            return $upload?->isPreviewable() ? $upload->temporaryUrl() : null;
        }

        $path = substr($token, 6);

        return str_contains($path, '/')
            ? Storage::disk('public')->url($path)
            : asset('img/xd/'.$path.'.jpg');
    }

    private function item(CatalogAdmin $catalog): Model
    {
        return $catalog->find($this->type, $this->itemId);
    }
}
