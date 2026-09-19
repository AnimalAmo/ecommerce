<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Concerns\ConfirmsCatalogActions;
use App\Models\Region\Region;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogIndex extends Component
{
    use ConfirmsCatalogActions, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $partner = '';

    #[Url(as: 'tipo', except: '')]
    public string $family = '';

    #[Url(as: 'regione', except: '')]
    public string $region = '';

    #[Url(as: 'stato', except: '')]
    public string $status = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'partner', 'family', 'region', 'status'], true)) {
            $this->resetPage();
        }
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return [
            'search' => $this->search,
            'partner' => $this->partner,
            'family' => $this->family,
            'region' => $this->region,
            'status' => $this->status,
        ];
    }

    public function render(CatalogAdmin $catalog, CatalogPresenter $presenter)
    {
        $page = $catalog->paginate($this->filters());
        $totals = $catalog->totals();

        return view('livewire.admin.catalog.index', [
            'page' => $page,
            'rows' => $page->getCollection()->map(fn ($item) => $presenter->row($item)),
            'totals' => $totals,
            'partners' => $catalog->partnerOptions(),
            'regions' => Region::query()->orderBy('name')->pluck('name', 'id'),
            'families' => CatalogPresenter::FAMILY_LABELS,
            'statuses' => collect(CatalogPresenter::STATUS_BADGES)->map(fn (array $badge) => $badge[0]),
            'exportUrl' => route('admin.catalog.export', array_filter($this->filters())),
        ])
            ->layout('layouts::admin')
            ->title('Catalogo');
    }
}
