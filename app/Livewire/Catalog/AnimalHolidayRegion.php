<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Livewire\Concerns\AddsEventToCart;
use App\Livewire\Concerns\HasCatalogFilters;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHolidayRegion extends Component
{
    use AddsEventToCart;
    use HasCatalogFilters;
    use TogglesFavorites;

    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function mount(string $region): void
    {
        $model = Region::where('slug', $region)->first();

        abort_unless($model !== null, 404);

        $this->regionSlug = $model->slug;
        $this->regionName = $model->name;
        $this->where = $this->regionName;
    }

    public function search(): void
    {
        // La ricerca "Dove" filtra le strutture in render(). Quando/ospiti/animali
        // non filtrano ancora (step 6 disponibilità).
    }

    /** Card mostrate sotto "Risultati simili alla tua ricerca:" quando i filtri non danno risultati (XD app "Nessun risultato"). */
    private const SIMILAR_LIMIT = 5;

    public function render()
    {
        $term = trim($this->where);

        // Il mock XD mostra gli stessi 12 risultati per ogni regione: se "Dove" è vuoto o
        // coincide col nome della regione (pre-compilato in mount) manteniamo quel comportamento;
        // se l'utente cambia il testo, filtriamo per nome OR location (LIKE %dove%, case-insensitive).
        $showAll = $term === '' || mb_strtolower($term) === mb_strtolower($this->regionName);

        // Hotel/servizi mappano sulle strutture; con una sola delle due attiva
        // filtriamo per ProductType, con entrambe (o nessuna delle altre) prendiamo tutto.
        $productTypes = array_values(array_intersect($this->activeTypes, ['hotel', 'servizi']));

        $results = $productTypes === []
            ? Structure::query()->whereRaw('1 = 0')->get()
            : Structure::query()
                ->when(! $showAll, function ($query) use ($term): void {
                    $like = self::like($term);
                    // name è JSON translatable: LIKE sul path del locale corrente,
                    // non sulla colonna raw (matcherebbe chiavi locale e testo cross-lingua).
                    $query->where(fn ($sub) => $sub->whereLike('name->'.app()->getLocale(), $like)->orWhereLike('location', $like));
                })
                ->when(count($productTypes) === 1, fn ($query) => $query->where(
                    'type',
                    $productTypes[0] === 'hotel' ? ProductType::Structure : ProductType::Service,
                ))
                // Fascia di prezzo: attiva solo se l'utente si è mosso dai default XD
                // (i seed hanno anche prezzi a 0 che ai default resterebbero esclusi).
                ->when($this->priceFiltered(), fn ($query) => $query->whereBetween('price_from_cents', $this->priceRangeCents()))
                ->orderBy('position')
                ->get();

        // Attività ed eventi (XD app "Cerca - risultati - click 'filtri' – 1"): stesse righe Event,
        // separate dal ProductType. Le chip del modal accendono una famiglia per volta.
        $eventTypes = array_values(array_filter([
            in_array('attivita', $this->activeTypes, true) ? ProductType::Activity : null,
            in_array('eventi', $this->activeTypes, true) ? ProductType::Event : null,
        ]));

        $events = $eventTypes === []
            ? Event::query()->whereRaw('1 = 0')->get()
            : Event::query()
                ->whereIn('type', $eventTypes)
                ->when(! $showAll, function ($query) use ($term): void {
                    $like = self::like($term);
                    $query->where(fn ($sub) => $sub->whereLike('title->'.app()->getLocale(), $like)->orWhereLike('location', $like));
                })
                ->when($this->priceFiltered(), fn ($query) => $query->whereBetween('price_cents', $this->priceRangeCents()))
                ->orderBy('position')
                ->get();

        // Smartbox (XD app "Cerca - risultati - click 'filtri' – 2"): le tre chip Soggiorno/
        // Benessere/Avventura restringono la selezione, nessuna chip = tutti i cofanetti.
        $boxTypes = array_values(array_map(
            fn (string $type): ProductType => self::SMARTBOX_PRODUCT_TYPES[$type],
            array_intersect(self::SMARTBOX_TYPES, $this->smartboxTypes),
        ));

        $boxes = ! in_array('smartbox', $this->activeTypes, true)
            ? SmartboxPackage::query()->whereRaw('1 = 0')->get()
            : SmartboxPackage::query()
                ->when($boxTypes !== [], fn ($query) => $query->whereIn('type', $boxTypes))
                ->when(! $showAll, fn ($query) => $query->whereLike('title->'.app()->getLocale(), self::like($term)))
                ->when($this->priceFiltered(), fn ($query) => $query->whereBetween('price_from_cents', $this->priceRangeCents()))
                ->orderBy('position')
                ->get();

        $empty = $results->isEmpty() && $events->isEmpty() && $boxes->isEmpty();

        // "Nessun risultato trovato": l'XD app non lascia la pagina vuota ma propone card
        // simili, cioè lo stesso catalogo senza i filtri (tipologia e prezzo) che l'hanno svuotato.
        $similar = $empty
            ? Structure::query()->orderBy('position')->limit(self::SIMILAR_LIMIT)->get()
            : $results;

        return view('livewire.catalog.animal-holiday-region', [
            'results' => $results,
            'events' => $events,
            'boxes' => $boxes,
            'empty' => $empty,
            'similar' => $similar,
        ])->title('AnimalAmo — '.__('catalog.region_title', ['region' => $this->regionName]));
    }
}
