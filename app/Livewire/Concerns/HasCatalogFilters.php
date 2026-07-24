<?php

namespace App\Livewire\Concerns;

use App\Enums\ProductType;

/**
 * Filtri catalogo mobile (XD app "Filtri 2 ricerca"): chip attive, fascia di prezzo,
 * tipologie e sotto-sezioni Smartbox. Condiviso dalle pagine che riusano il modal.
 */
trait HasCatalogFilters
{
    /** Tipologie del modal "Filtri 2" (XD app), nell'ordine della griglia. */
    public const FILTER_TYPES = ['hotel', 'servizi', 'attivita', 'eventi', 'smartbox'];

    /** Tipologie Smartbox (XD app "Filtri 2 ricerca - click su 'soggiorno'"). */
    public const SMARTBOX_TYPES = ['soggiorno', 'benessere', 'avventura'];

    /** Chip Smartbox del modal → ProductType dei cofanetti. */
    private const SMARTBOX_PRODUCT_TYPES = [
        'soggiorno' => ProductType::Stay,
        'benessere' => ProductType::Wellness,
        'avventura' => ProductType::Adventure,
    ];

    /** Estremi della fascia di prezzo (XD: '9 €' – '451 €'). */
    public const PRICE_MIN = 9;

    public const PRICE_MAX = 451;

    /**
     * Chip filtro attive su mobile (XD app "Cerca - risultati"). Ogni pagina decide
     * quali tipologie sa filtrare: quelle che non mappano sul suo catalogo restano
     * nel modal per fedeltà XD ma non producono risultati. La X rimuove una tipologia,
     * rimuovere anche l'ultima ripristina il set di default della pagina.
     *
     * Dichiarata vuota qui e popolata dall'hook di mount: PHP vieta a una classe che
     * usa il trait di ridichiarare la proprietà con un default diverso, quindi le
     * pagine differenziano il set iniziale via defaultFilterTypes().
     */
    public array $activeTypes = [];

    /** Fascia di prezzo dal modal Filtri (€); filtra solo se diversa dai default. */
    public int $priceMin = self::PRICE_MIN;

    public int $priceMax = self::PRICE_MAX;

    /** Tipologie Smartbox selezionate nel modal: restringono i cofanetti dove la pagina li elenca. */
    public array $smartboxTypes = [];

    /** Checkbox "Numero di persone" del modal (solo UI: non filtra ancora nulla). */
    public array $peopleGroups = [];

    /** Livewire richiama da solo gli hook di mount dei trait. */
    public function mountHasCatalogFilters(): void
    {
        $this->activeTypes = $this->defaultFilterTypes();
    }

    /** Tipologie accese all'apertura della pagina; le pagine con altri cataloghi la sovrascrivono. */
    protected function defaultFilterTypes(): array
    {
        return ['hotel', 'servizi'];
    }

    /** Hook dopo ogni cambio di filtro: le pagine paginate lo sovrascrivono per tornare a pagina 1. */
    protected function onFiltersChanged(): void {}

    /** X sulla chip fascia di prezzo (mobile): torna ai default = filtro spento. */
    public function resetPrice(): void
    {
        $this->priceMin = self::PRICE_MIN;
        $this->priceMax = self::PRICE_MAX;

        $this->onFiltersChanged();
    }

    /** X su una chip tipologia (mobile). */
    public function removeType(string $type): void
    {
        $this->activeTypes = array_values(array_diff($this->activeTypes, [$type]));

        if ($this->activeTypes === []) {
            $this->activeTypes = $this->defaultFilterTypes();
        }

        $this->onFiltersChanged();
    }

    /** Card tipologia nel modal Filtri: toggle multi-selezione. */
    public function toggleType(string $type): void
    {
        if (! in_array($type, self::FILTER_TYPES, true)) {
            return;
        }

        if (in_array($type, $this->activeTypes, true)) {
            $this->removeType($type);

            return;
        }

        $this->activeTypes[] = $type;

        if ($type === 'smartbox') {
            // Deselezionando Smartbox spariscono anche le sue sotto-sezioni: reset alla riapertura.
            $this->smartboxTypes = [];
            $this->peopleGroups = [];
        }

        $this->onFiltersChanged();
    }

    /** Card "Tipologia Smartbox" nel modal: toggle multi-selezione. */
    public function toggleSmartboxType(string $type): void
    {
        if (! in_array($type, self::SMARTBOX_TYPES, true)) {
            return;
        }

        $this->smartboxTypes = in_array($type, $this->smartboxTypes, true)
            ? array_values(array_diff($this->smartboxTypes, [$type]))
            : [...$this->smartboxTypes, $type];

        $this->onFiltersChanged();
    }

    /** Le pill dropdown desktop scrivono le tipologie Smartbox via wire:model: stesso hook dei toggle. */
    public function updatedSmartboxTypes(): void
    {
        $this->onFiltersChanged();
    }

    public function updatedPriceMin(): void
    {
        $this->normalizePriceRange();
    }

    public function updatedPriceMax(): void
    {
        $this->normalizePriceRange();
    }

    /** Mantiene la fascia dentro gli estremi XD e min ≤ max (slider e input liberi). */
    private function normalizePriceRange(): void
    {
        $this->priceMin = max(self::PRICE_MIN, min($this->priceMin, self::PRICE_MAX));
        $this->priceMax = max(self::PRICE_MIN, min($this->priceMax, self::PRICE_MAX));

        if ($this->priceMin > $this->priceMax) {
            [$this->priceMin, $this->priceMax] = [$this->priceMax, $this->priceMin];
        }

        $this->onFiltersChanged();
    }

    /** La fascia di prezzo filtra solo se l'utente si è mosso dagli estremi XD. */
    private function priceFiltered(): bool
    {
        return $this->priceMin !== self::PRICE_MIN || $this->priceMax !== self::PRICE_MAX;
    }

    /** @return array{int, int} */
    private function priceRangeCents(): array
    {
        return [$this->priceMin * 100, $this->priceMax * 100];
    }

    private static function like(string $term): string
    {
        return '%'.addcslashes($term, '\%_').'%';
    }
}
