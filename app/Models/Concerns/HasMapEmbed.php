<?php

namespace App\Models\Concerns;

/**
 * Sezione "Dove siamo": Google Maps Embed quando services.google.maps_key è
 * configurata, altrimenti lo screenshot statico dell'XD (mapFallbackUrl).
 * Con la chiave la mappa appare anche per i prodotti partner, che non hanno
 * screenshot ma hanno località/indirizzo.
 */
trait HasMapEmbed
{
    /** Query "place" per la Maps Embed API. */
    abstract public function mapQuery(): ?string;

    /** Screenshot statico da mostrare senza chiave. */
    abstract public function mapFallbackUrl(): ?string;

    public function hasMap(): bool
    {
        return (filled(config('services.google.maps_key')) && filled($this->mapQuery()))
            || filled($this->mapFallbackUrl());
    }
}
