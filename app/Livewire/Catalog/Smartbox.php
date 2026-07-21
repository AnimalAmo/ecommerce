<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Livewire\Concerns\HasCatalogFilters;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\SmartboxPackage\SmartboxPackage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Smartbox extends Component
{
    use HasCatalogFilters;
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

    /** Tetto al "Carica altro": oltre non è più una lista ma un dump del catalogo. */
    private const MAX_PER_PAGE = 120;

    /** Card mostrate sotto "Risultati simili alla tua ricerca:" quando i filtri non danno risultati (XD app "Nessun risultato"). */
    private const SIMILAR_LIMIT = 5;

    /**
     * Filtro testuale della pill di ricerca mobile: deep-linkabile (?cerca=…).
     * I cofanetti non hanno una colonna luogo (il "Luogo" dei filtri desktop è ancora un TODO),
     * quindi il termine cerca nel titolo del pacchetto.
     */
    #[Url(as: 'cerca')]
    public string $where = '';

    public string $guests = '';

    public string $animals = '';

    /**
     * Quante card carica la griglia: cresce col "Carica altro" mobile.
     * È una proprietà pubblica, quindi il client può rispedirla alterata nel payload:
     * il valore va sempre riletto tramite pageSize(), mai usato così com'è.
     */
    public int $pageSize = self::PER_PAGE;

    public function search(): void
    {
        $this->resetPage();
    }

    /** "Carica altro" (XD app): allunga la prima pagina di un blocco invece di impaginare. */
    public function loadMore(): void
    {
        $this->pageSize = $this->pageSize() + self::PER_PAGE;
    }

    public function render()
    {
        // Questa pagina elenca solo cofanetti: spegnere la tipologia Smartbox nel modal
        // Filtri svuota la griglia, le altre chip restano inerti qui.
        $boxes = (in_array('smartbox', $this->activeTypes, true)
            ? SmartboxPackage::query()
            : SmartboxPackage::query()->whereRaw('1 = 0'))
            // Chip Soggiorno/Benessere/Avventura: nessuna accesa = tutti i cofanetti.
            ->when($this->smartboxProductTypes() !== [], fn (Builder $query) => $query->whereIn('type', $this->smartboxProductTypes()))
            ->when(trim($this->where) !== '', fn (Builder $query) => $query->whereLike('title->'.app()->getLocale(), self::like(trim($this->where))))
            // Fascia di prezzo: attiva solo se l'utente si è mosso dai default XD.
            ->when($this->priceFiltered(), fn (Builder $query) => $query->whereBetween('price_from_cents', $this->priceRangeCents()))
            // Ordine di griglia XD (riga per riga).
            ->orderBy('position')
            ->paginate($this->pageSize());

        $empty = $boxes->isEmpty();

        // "Nessun risultato trovato": l'XD app non lascia la pagina vuota ma propone card
        // simili, cioè lo stesso catalogo senza i filtri che l'hanno svuotato.
        $similar = $empty
            ? SmartboxPackage::query()->orderBy('position')->limit(self::SIMILAR_LIMIT)->get()
            : $boxes->getCollection();

        return view('livewire.catalog.smartbox', [
            'boxes' => $boxes,
            'empty' => $empty,
            'similar' => $similar,
        ])->title(__('smartbox.meta_title'));
    }

    /** La pagina Smartbox parte con la sola tipologia che sa elencare. */
    protected function defaultFilterTypes(): array
    {
        return ['smartbox'];
    }

    /** Cambiare un filtro stando su una pagina avanzata atterrerebbe su una pagina vuota. */
    protected function onFiltersChanged(): void
    {
        $this->resetPage();
    }

    /**
     * Chip Smartbox selezionate → ProductType dei cofanetti.
     *
     * @return array<int, ProductType>
     */
    private function smartboxProductTypes(): array
    {
        return array_values(array_map(
            fn (string $type): ProductType => self::SMARTBOX_PRODUCT_TYPES[$type],
            array_intersect(self::SMARTBOX_TYPES, $this->smartboxTypes),
        ));
    }

    /** Dimensione pagina bonificata: il payload client è arbitrario, la teniamo tra un blocco e il tetto. */
    private function pageSize(): int
    {
        return max(self::PER_PAGE, min($this->pageSize, self::MAX_PER_PAGE));
    }
}
