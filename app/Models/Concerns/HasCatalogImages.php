<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Risolve le colonne immagine del catalogo in URL. Convive con due sorgenti:
 * - stem del template XD ('regione-hotel-brescia') => asset img/xd/<stem>.jpg
 * - path di upload partner ('structure-photos/abc.jpg', disco public) => Storage URL
 * I blade usano SEMPRE questi accessor, mai la concatenazione manuale.
 */
trait HasCatalogImages
{
    public function imageUrl(): ?string
    {
        return $this->resolveImage($this->img);
    }

    public function heroImageUrl(): ?string
    {
        return $this->resolveImage($this->hero_img);
    }

    public function mapImageUrl(): ?string
    {
        return $this->resolveImage($this->map_img ?? null);
    }

    /**
     * Le foto di «Vedere tutte le foto»: la copertina in testa, perché è la
     * foto che il cliente ha già davanti nell'hero, poi la galleria che il
     * publisher fotografa dalla bozza, senza ripetizioni. Il pulsante compare
     * solo con almeno due foto: le schede del catalogo demo hanno la sola
     * copertina del template e non lo mostrano.
     *
     * La copertina si legge da `hero_img` e non si dà per scontato che apra la
     * galleria: una scheda ha una modifica aperta quando il travaso della
     * migrazione copia la bozza, e se in quella modifica il partner ha tolto
     * la copertina, l'hero la mostra ancora ma la bozza no.
     *
     * @return list<string>
     */
    public function galleryImageUrls(): array
    {
        $paths = array_unique(array_filter([$this->hero_img, ...($this->gallery ?? [])], filled(...)));

        return array_values(array_map($this->resolveImage(...), $paths));
    }

    /**
     * Le foto che il pulsante e il modale della galleria ricevono: vuoto
     * quando c'è una foto sola. È l'unico posto della soglia, così pulsante e
     * modale non possono divergere (un pulsante senza modale tornerebbe morto).
     *
     * @return list<string>
     */
    public function galleryPhotos(): array
    {
        $urls = $this->galleryImageUrls();

        return count($urls) > 1 ? $urls : [];
    }

    private function resolveImage(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return str_contains($value, '/')
            ? Storage::disk('public')->url($value)
            : asset('img/xd/'.$value.'.jpg');
    }
}
