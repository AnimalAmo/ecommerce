<?php

namespace App\Livewire;

use Livewire\Component;

class Favorites extends Component
{
    /** Filtro "Tipologia" attivo dal dropdown sopra il contenitore (null = tutte). */
    public ?string $typeFilter = null;

    /** Lista preferiti in-memory (il cuore sulla card rimuove); niente DB. */
    public array $favorites = [];

    /** Id già aggiunti al carrello (toggle del bottone borsa sulla card). */
    public array $inCart = [];

    /** Tipologie del menu "Tipologia": i 5 tag distinti presenti in pagina. */
    public const TYPES = ['Attività', 'Struttura', 'Evento', 'Benessere', 'Soggiorno'];

    /**
     * Preferiti campione come da XD (artboard "Preferiti – 2", ordine griglia:
     * riga 1 sx→centro→dx poi riga 2); statici come nelle pagine sorelle,
     * struttura pronta per essere sostituita da un backend reale.
     */
    public const FAVORITES = [
        [
            'id' => 1,
            'title' => 'Vacanza di relax in montagna',
            'location' => 'Alpi, Italia',
            'tag' => 'Attività',
            'tagColor' => '#8E53E6',
            'metaType' => 'durata',
            'metaText' => 'DURATA DI 5 GIORNI',
            'photo' => 'favorites-activity-mountain.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 2,
            'title' => 'Hotel con piscina sul lago',
            'location' => 'Como, Italia',
            'tag' => 'Struttura',
            'tagColor' => '#FF9F3E',
            'metaType' => 'rating',
            'metaText' => '4,5',
            'photo' => 'favorites-hotel-lake.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 3,
            'title' => 'Sessione pomeridiana di Puppy Yoga',
            'location' => 'Milano, Italia',
            'tag' => 'Evento',
            'tagColor' => '#C59FFD',
            'metaType' => 'data',
            'metaText' => 'LUN, 30 MAG ALLE 15:30',
            'photo' => 'favorites-puppy-yoga.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 4,
            'title' => 'Weekend di relax in Lombardia',
            'location' => 'San Pellegrino Terme, Italia',
            'tag' => 'Benessere',
            'tagColor' => '#8DABFF',
            'metaType' => 'persone',
            'metaText' => '2 persone',
            'photo' => 'favorites-wellness-lombardia.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 5,
            'title' => 'Pomeriggio di addestramento',
            'location' => 'Milano, Italia',
            'tag' => 'Evento',
            'tagColor' => '#C59FFD',
            'metaType' => 'data',
            'metaText' => 'SAB, 25 MAG ALLE ORE 15:00',
            'photo' => 'favorites-training.jpg',
            'price' => '0,00 €',
        ],
        [
            'id' => 6,
            'title' => 'Weekend di relax in Lombardia',
            'location' => 'Como, Italia',
            'tag' => 'Soggiorno',
            'tagColor' => '#8DE0FF',
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
        // TODO: backend reale — per ora la lista vive solo in memoria per la durata del componente.
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
        $this->typeFilter = in_array($type, self::TYPES, true) ? $type : null;
    }

    public function render()
    {
        // Filtro tipologia sulla lista corrente (match sul tag della card).
        $visibleFavorites = $this->typeFilter === null
            ? $this->favorites
            : array_values(array_filter(
                $this->favorites,
                fn (array $item): bool => $item['tag'] === $this->typeFilter,
            ));

        return view('livewire.favorites', [
            'visibleFavorites' => $visibleFavorites,
            'types' => self::TYPES,
        ])->title('Preferiti — AnimalAmo');
    }
}
