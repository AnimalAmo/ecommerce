<?php

namespace App\Livewire\Concerns;

use App\Services\Partner\Publishing\FamilyPublisher;
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

    /**
     * Toglie una foto già salvata: dalla bozza subito (il partner la vede
     * sparire), dal disco solo se nessuna pagina la mostra più.
     *
     * Difetto F1 (audit 27/09/2026): qui il file si cancellava nello stesso
     * clic, fuori dal ciclo salva step → completa → pubblica. Se era la
     * copertina di un servizio già pubblicato, `img`/`hero_img` a catalogo la
     * puntavano ancora e la scheda pubblica serviva un'immagine rotta; se poi
     * il partner abbandonava la modifica (magari bloccato da collectPhotos()
     * sotto il minimo), la scheda restava online così e il file era perso.
     *
     * Ora decide FamilyPublisher::deletePhotoIfUnreferenced(): una foto che
     * nessuna pagina mostra (tutte quelle di una bozza mai pubblicata) si
     * cancella subito; una che il catalogo o uno storico ordini punta ancora
     * resta su disco. Dal 29/09/2026 il catalogo punta ogni foto di una scheda
     * pubblicata, non solo la copertina: la galleria («Vedere tutte le foto»)
     * le mostra tutte. Le pota il publisher quando la versione nuova è a
     * catalogo; se il partner abbandona, restano dove sono e la scheda online
     * resta integra.
     *
     * La fonte di verità è la bozza, non `saved`: `saved` è una proprietà
     * pubblica che il client può riscrivere. Prima si salvava `saved` nella
     * bozza e solo dopo si controllava il path contro la bozza, quindi due
     * chiamate forgiate bastavano (la prima scriveva un path altrui nella
     * bozza, la seconda lo trovava "posseduto" e lo cancellava). Ora un path
     * che la bozza non contiene non tocca niente, e `saved` si riallinea.
     */
    public function removeSaved(int $index): void
    {
        $draft = $this->draft();
        $current = $draft->photos ?? [];
        $path = $this->saved[$index] ?? null;

        if ($path === null || ! in_array($path, $current, true)) {
            $this->saved = $current;

            return;
        }

        $this->saved = array_values(array_filter($current, fn (string $photo): bool => $photo !== $path));
        $draft->update(['photos' => $this->saved]);

        FamilyPublisher::deletePhotoIfUnreferenced($path);
    }

    /**
     * Le foto già salvate che la bozza possiede davvero, nell'ordine del
     * client. `saved` arriva dal payload: senza questo filtro un path qualsiasi
     * del disco public entrava nella bozza con next(), contava nel minimo e
     * finiva in copertina a catalogo. array_unique perché array_intersect
     * conserva i doppioni: lo stesso path ripetuto quattro volte avrebbe
     * soddisfatto il minimo con una foto sola.
     */
    protected function ownedSavedPhotos(): array
    {
        return array_values(array_unique(array_intersect($this->saved, $this->draft()->photos ?? [])));
    }

    /**
     * Valida e memorizza le foto, restituendo i percorsi salvati + nuovi;
     * restituisce null (aggiungendo l'errore) se il minimo non è raggiunto.
     */
    protected function collectPhotos(): ?array
    {
        $saved = $this->ownedSavedPhotos();

        if (count($saved) + count($this->photos) < $this->minPhotos) {
            $this->addError('photos', $this->photoMinError());

            return null;
        }

        $this->validate(['photos.*' => ['image', 'max:8192']]);

        $paths = $saved;
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
