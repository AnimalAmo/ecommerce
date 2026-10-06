<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Region\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * /eventi (cliente, 06/10/2026): come Animal Holiday, prima le regioni con la
 * foto, poi cliccando la lista di quella regione (Events su eventi.region).
 * Prende il posto della barra di pillole con tutte le regioni.
 *
 * Le 20 regioni restano tutte, anche senza schede, per la stessa ragione di
 * Animal Holiday: sono dati veri e la sola strada verso la pagina regione. Il
 * badge conta solo attività ed eventi davvero visibili e non ancora finiti.
 */
class EventsRegions extends Component
{
    /** "Dove": restringe le regioni per nome, come su Animal Holiday. */
    #[Url(as: 'dove')]
    public string $where = '';

    public function search(): void
    {
        // Il filtro si applica in render().
    }

    public function render()
    {
        $term = trim($this->where);

        return view('livewire.catalog.events-regions', [
            'regions' => Region::query()
                ->withCount(['events as published_events_count' => fn (Builder $query) => $query
                    ->whereIn('type', [ProductType::Activity, ProductType::Event])
                    ->upcoming()])
                ->when($term !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes($term, '\%_').'%'))
                ->orderBy('position')
                ->get(),
            'catalogueEmpty' => Event::query()
                ->whereIn('type', [ProductType::Activity, ProductType::Event])
                ->upcoming()
                ->doesntExist(),
        ])->title(__('events.meta_title'));
    }
}
