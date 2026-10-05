<?php

namespace App\Services\Admin\Catalog;

use App\Models\SmartboxPackage\SmartboxPackage;
use App\Services\Partner\Publishing\FamilyPublisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Foto di una scheda pubblicata, dal pannello (richiesta della cliente,
 * 01/10/2026): aggiungere, togliere, riordinare, scegliere la copertina.
 *
 * Scrive la riga a catalogo e la bozza del partner nella stessa transazione,
 * come CatalogAdmin::update() fa con i testi. Solo la riga non basterebbe: alla
 * prossima ripubblicazione FamilyPublisher::pruneReplacedPhotos() troverebbe a
 * catalogo foto che la bozza non ha, e le cancellerebbe dal disco. Niente
 * ripubblicazione (DraftCompleter): metterebbe online anche le altre modifiche
 * lasciate a metà dal partner, e si fermerebbe davanti a un partner non pagabile.
 *
 * Una modifica alle foto ancora aperta nel wizard del partner viene sostituita:
 * la scheda admin lo dice prima del salvataggio (hasPendingPartnerChanges()).
 */
class CatalogPhotoEditor
{
    public const MIN_PHOTOS = 4;

    /**
     * Le foto della scheda nell'ordine del sito: la copertina, poi la galleria,
     * senza ripetizioni. La stessa regola di HasCatalogImages::galleryImageUrls(),
     * ma sui path e non sugli URL.
     *
     * @return list<string>
     */
    public function current(Model $item): array
    {
        return array_values(array_unique(array_filter([$item->hero_img, ...($item->gallery ?? [])], filled(...))));
    }

    /** Il partner ha cambiato le foto nel wizard e non ha ancora ripubblicato. */
    public function hasPendingPartnerChanges(Model $item): bool
    {
        $draft = $item->draft;

        return $draft !== null && array_values($draft->photos ?? []) !== $this->current($item);
    }

    /**
     * @param  list<string|UploadedFile>  $photos  l'ordine voluto: path già della scheda o file nuovi.
     *                                             La prima è la copertina.
     *
     * @throws ValidationException sotto la chiave `photos`, con meno di MIN_PHOTOS foto
     */
    public function save(Model $item, array $photos): void
    {
        $current = $this->current($item);

        // Un path che la scheda non ha non entra: il payload del client si può
        // riscrivere, e senza questo filtro una foto di un'altra scheda finirebbe
        // qui (e poi potata da lì). Niente doppioni: due volte la stessa foto non
        // fanno due foto per il minimo.
        $seen = [];
        $photos = array_values(array_filter($photos, function ($photo) use ($current, &$seen): bool {
            if ($photo instanceof UploadedFile) {
                return true;
            }

            if (! is_string($photo) || ! in_array($photo, $current, true) || isset($seen[$photo])) {
                return false;
            }

            return $seen[$photo] = true;
        }));

        if (count($photos) < self::MIN_PHOTOS) {
            throw ValidationException::withMessages(['photos' => __('admin-catalog.create.photos_min')]);
        }

        $directory = $item instanceof SmartboxPackage ? 'smartbox-photos' : 'structure-photos';
        $stored = [];

        try {
            $paths = array_map(function ($photo) use ($directory, &$stored): string {
                if (is_string($photo)) {
                    return $photo;
                }

                return $stored[] = $photo->store($directory, 'public');
            }, $photos);

            DB::transaction(function () use ($item, $paths): void {
                $row = $item->newQueryWithoutScopes()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
                // Anche la bozza sotto lock: uno step foto del partner salvato in
                // mezzo verrebbe sovrascritto alla cieca, e la potatura qui sotto
                // leggerebbe foto della bozza che non ci sono più.
                $draft = $row->draft()->lockForUpdate()->first();

                $before = [...$this->current($row), (string) $row->img, ...($draft?->photos ?? [])];

                $row->forceFill(['img' => $paths[0], 'hero_img' => $paths[0], 'gallery' => $paths])->save();
                $draft?->forceFill(['photos' => $paths])->save();

                // Dopo il commit, come pruneReplacedPhotos(): un rollback lascia
                // la scheda com'era e i suoi file con lei. Gli stem del template
                // XD (senza '/') sono asset versionati, mai file da cancellare.
                $removed = array_unique(array_diff(array_filter($before, fn (string $path): bool => str_contains($path, '/')), $paths));

                foreach ($removed as $path) {
                    DB::afterCommit(fn () => FamilyPublisher::deletePhotoIfUnreferenced($path));
                }
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored);

            throw $e;
        }

        $item->refresh();
    }
}
