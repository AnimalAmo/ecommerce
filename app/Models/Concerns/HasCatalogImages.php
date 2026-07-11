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
