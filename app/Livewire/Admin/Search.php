<?php

namespace App\Livewire\Admin;

use App\Services\Admin\Catalog\CatalogPresenter;
use App\Services\Admin\Search\GlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ricerca globale: la pagina a cui porta il campo in testa al layout
 * (GET admin.search?q=…). Schede, iscritti e ordini, raggruppati.
 */
class Search extends Component
{
    #[Url(except: '')]
    public string $q = '';

    public function render(GlobalSearch $search, CatalogPresenter $presenter)
    {
        $term = trim($this->q);
        $searchable = $search->isSearchable($term);

        $catalog = $searchable ? $search->catalog($term) : ['items' => collect(), 'total' => 0];
        $users = $searchable ? $search->users($term) : ['items' => collect(), 'total' => 0];
        $orders = $searchable ? $search->orders($term) : ['items' => collect(), 'total' => 0];

        return view('livewire.admin.search', [
            'term' => $term,
            'searchable' => $searchable,
            'catalog' => [
                'rows' => $catalog['items']->map(fn (Model $item): array => $presenter->row($item)),
                'total' => $catalog['total'],
            ],
            'users' => $users,
            'orders' => $orders,
            'searchesOrders' => $search->searchesOrders($term),
            'total' => $catalog['total'] + $users['total'] + $orders['total'],
        ])
            ->layout('layouts::admin')
            ->title(__('admin-dashboard.search.title'));
    }
}
