<?php

namespace App\Livewire;

use App\Enums\ProductType;
use Livewire\Component;

class Favorites extends Component
{
    /** Filtro "Tipologia" attivo dal dropdown sopra il contenitore (value ProductType, null = tutte). */
    public ?string $typeFilter = null;

    /** Lista preferiti in-memory (il cuore sulla card rimuove); persistenza con lo step 2. */
    public array $favorites = [];

    /** Id già aggiunti al carrello (toggle del bottone borsa sulla card). */
    public array $inCart = [];

    /** Tipologie del menu "Tipologia": i 5 tipi distinti presenti in pagina. */
    public const TYPES = [
        ProductType::Activity,
        ProductType::Structure,
        ProductType::Event,
        ProductType::Wellness,
        ProductType::Stay,
    ];

    /**
     * Preferiti campione come da XD (artboard "Preferiti – 2", ordine griglia:
     * riga 1 sx→centro→dx poi riga 2); citano prodotti fuori catalogo mock, quindi
     * restano dati campione finché i preferiti non saranno persistenti (step 2).
     * 'type' è il value ProductType: label e colore chip arrivano dall'enum.
     */
    public const FAVORITES = [
        [
            'id' => 1,
            'title' => 'Vacanza di relax in montagna',
            'location' => 'Alpi, Italia',
            'type' => 'activity',
            'metaType' => 'durata',
            'metaText' => 'DURATA DI 5 GIORNI',
            'photo' => 'favorites-activity-mountain.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 2,
            'title' => 'Hotel con piscina sul lago',
            'location' => 'Como, Italia',
            'type' => 'structure',
            'metaType' => 'rating',
            'metaText' => '4,5',
            'photo' => 'favorites-hotel-lake.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 3,
            'title' => 'Sessione pomeridiana di Puppy Yoga',
            'location' => 'Milano, Italia',
            'type' => 'event',
            'metaType' => 'data',
            'metaText' => 'LUN, 30 MAG ALLE 15:30',
            'photo' => 'favorites-puppy-yoga.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 4,
            'title' => 'Weekend di relax in Lombardia',
            'location' => 'San Pellegrino Terme, Italia',
            'type' => 'wellness',
            'metaType' => 'persone',
            'metaText' => '2 persone',
            'photo' => 'favorites-wellness-lombardia.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 5,
            'title' => 'Pomeriggio di addestramento',
            'location' => 'Milano, Italia',
            'type' => 'event',
            'metaType' => 'data',
            'metaText' => 'SAB, 25 MAG ALLE ORE 15:00',
            'photo' => 'favorites-training.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 6,
            'title' => 'Weekend di relax in Lombardia',
            'location' => 'Como, Italia',
            'type' => 'stay',
            'metaType' => 'persone',
            'metaText' => '2 persone',
            'photo' => 'favorites-stay-como.jpg',
            'price' => '0,00 €',
        ],
    ];

    public function mount(): void
    {
        $this->favorites = self::FAVORITES;
    }

    /** Il cuore sulla card rimuove il preferito. */
    public function removeFavorite(int $id): void
    {
        // TODO: backend reale (step 2) — per ora la lista vive solo in memoria per la durata del componente.
        $this->favorites = array_values(array_filter(
            $this->favorites,
            fn (array $item): bool => $item['id'] !== $id,
        ));

        $this->inCart = array_values(array_diff($this->inCart, [$id]));
    }

    /** Il bottone borsa aggiunge/toglie dal carrello (per ora solo stato visivo). */
    public function toggleCart(int $id): void
    {
        // TODO: backend reale.
        if (in_array($id, $this->inCart, true)) {
            $this->inCart = array_values(array_diff($this->inCart, [$id]));
        } else {
            $this->inCart[] = $id;
        }
    }

    /** Selezione dal menu "Tipologia"; null (voce "Tutte") azzera il filtro. */
    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = in_array(ProductType::tryFrom($type ?? ''), self::TYPES, true) ? $type : null;
    }

    public function render()
    {
        // Filtro tipologia sulla lista corrente (match sul tipo della card).
        $visibleFavorites = $this->typeFilter === null
            ? $this->favorites
            : array_values(array_filter(
                $this->favorites,
                fn (array $item): bool => $item['type'] === $this->typeFilter,
            ));

        return view('livewire.favorites', [
            'visibleFavorites' => $visibleFavorites,
            'types' => self::TYPES,
        ])->title('Preferiti — AnimalAmo');
    }
}
