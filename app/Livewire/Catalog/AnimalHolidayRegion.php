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
use Illuminate\Contracts\Database\Eloquent\Builder;
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

        // regionSlug è una proprietà pubblica: il client può riscriverla nel payload,
        // quindi la regione si rilegge dal database invece di fidarsi dello stato.
        $regionId = Region::where('slug', $this->regionSlug)->value('id');

        abort_unless($regionId !== null, 404);

        // "Dove" vuoto o uguale al nome della regione (pre-compilato in mount) = nessun
        // filtro testuale; se l'utente cambia il testo, filtriamo per nome OR location
        // (LIKE %dove%, case-insensitive).
        $showAll = $term === '' || mb_strtolower($term) === mb_strtolower($this->regionName);

        // Hotel/servizi mappano sulle strutture; con una sola delle due attiva
        // filtriamo per ProductType, con entrambe (o nessuna delle altre) prendiamo tutto.
        $productTypes = array_values(array_intersect($this->activeTypes, ['hotel', 'servizi']));

        $results = $productTypes === []
            ? Structure::query()->whereRaw('1 = 0')->get()
            : $this->regionStructures($regionId)
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
        // simili, cioè il catalogo DELLA REGIONE senza i filtri (tipologia e prezzo) che
        // l'hanno svuotato. Se anche così non c'è niente, la collection resta vuota e il
        // template non disegna il titolo "Risultati simili" sopra una griglia senza card.
        $similar = $empty
            ? $this->regionStructures($regionId)->orderBy('position')->limit(self::SIMILAR_LIMIT)->get()
            : $results;

        return view('livewire.catalog.animal-holiday-region', [
            'results' => $results,
            'events' => $events,
            'boxes' => $boxes,
            'empty' => $empty,
            // Griglia vuota per due motivi opposti: filtri troppo stretti oppure catalogo
            // ancora vuoto. Il rosso "prova a modificare i filtri" si mostra solo nel primo
            // caso: dare la colpa a filtri che il visitatore non ha toccato, quando nessun
            // filtro produrrebbe risultati, è una bugia.
            'filtersCanHelp' => $empty && $this->filtersCanStillHelp($showAll),
            'similar' => $similar,
        ])->title('AnimalAmo — '.__('catalog.region_title', ['region' => $this->regionName]));
    }

    /**
     * Base delle query strutture: solo quelle della regione aperta.
     *
     * Prima la query girava su tutto Structure senza filtro (il mock XD mostrava le
     * stesse 12 card in ogni regione): con un catalogo vero, una struttura pubblicata
     * in Sicilia sarebbe comparsa anche su /animal-holiday/liguria. Le strutture dei
     * partner hanno sempre region_id (StructurePublisher lo deriva dalla provincia).
     * Le 12 righe del mock XD non ce l'hanno: restano visibili solo dove il catalogo
     * finto è seminato (locale e test), mai su animalamo.it dove il flag è spento.
     */
    private function regionStructures(int $regionId): Builder
    {
        // reviews_count: la card mostra il voto solo se qualcuno l'ha davvero dato.
        return Structure::query()->withCount('reviews')->where(function (Builder $query) use ($regionId): void {
            $query->where('region_id', $regionId);

            if (config('app.seed_demo_data')) {
                $query->orWhereNull('region_id');
            }
        });
    }

    /**
     * Suggerire di allargare i filtri è onesto solo se allargarli può cambiare qualcosa:
     * o li ha ristretti il visitatore, o esiste dell'altro catalogo (eventi, cofanetti)
     * che le tipologie di default — hotel e servizi — tengono fuori dalla griglia.
     * Le due exists() girano solo a griglia vuota e senza filtri del visitatore.
     */
    private function filtersCanStillHelp(bool $showAll): bool
    {
        return $this->userNarrowedTheSearch($showAll)
            || Event::query()->exists()
            || SmartboxPackage::query()->exists();
    }

    /** Il visitatore ha ristretto qualcosa? Tipologie, fascia di prezzo, sotto-sezioni Smartbox o "Dove". */
    private function userNarrowedTheSearch(bool $showAll): bool
    {
        return ! $showAll
            || $this->priceFiltered()
            || $this->smartboxTypes !== []
            || array_values(array_intersect(self::FILTER_TYPES, $this->activeTypes)) !== $this->defaultFilterTypes();
    }
}
