<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Concerns\ConfirmsCatalogActions;
use App\Models\Region\Region;
use App\Services\Admin\Catalog\AdminServiceCreator;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use Illuminate\Validation\Rule;
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

    #[Url(as: 'type', except: '')]
    public string $family = '';

    #[Url(except: '')]
    public string $region = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * Partner e famiglia scelti nella modale "Nuova scheda". Non sono
     * `#[Url]`: sono una scelta di passaggio, non un filtro dell'elenco, e
     * finirebbero nel link "Esporta" insieme ai filtri veri.
     */
    public string $newPartner = '';

    public string $newFamily = 'structure';

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

    /**
     * Dalla modale alla pagina di creazione. Le due liste si ricontrollano
     * qui, sul server: il select offre solo partner idonei, ma il payload
     * Livewire è del client.
     */
    public function startCreate(AdminServiceCreator $creator): void
    {
        $this->validate([
            'newPartner' => ['required', Rule::in($creator->eligiblePartners()->keys()->map(strval(...))->all())],
            'newFamily' => ['required', Rule::in(AdminServiceCreator::CREATABLE_FAMILIES)],
        ], [
            'newPartner.required' => __('admin-catalog.create.partner_required', [], 'it'),
            'newPartner.in' => __('admin-catalog.create.partner_required', [], 'it'),
            'newFamily.required' => __('admin-catalog.create.entry.family_required', [], 'it'),
            'newFamily.in' => __('admin-catalog.create.entry.family_required', [], 'it'),
        ]);

        $this->redirectRoute('admin.catalog.create', [
            'family' => $this->newFamily,
            'partner' => (int) $this->newPartner,
        ], navigate: true);
    }

    public function render(CatalogAdmin $catalog, CatalogPresenter $presenter, AdminServiceCreator $creator)
    {
        $page = $catalog->paginate($this->filters());
        $totals = $catalog->totals();

        return view('livewire.admin.catalog.index', [
            'page' => $page,
            'rows' => $page->getCollection()->map(fn ($item) => $presenter->row($item)),
            'totals' => $totals,
            'partners' => $catalog->partnerOptions(),
            'regions' => Region::query()->orderBy('name')->pluck('name', 'id'),
            'families' => CatalogPresenter::familyLabels(),
            'statuses' => CatalogPresenter::statusLabels(),
            'exportUrl' => route('admin.catalog.export', array_filter($this->filters())),
            // Partner a cui si può intestare una scheda nuova: non è
            // partnerOptions(), che elenca solo chi ha già righe a catalogo —
            // e un partner nuovo non ne ha nessuna.
            'creatablePartners' => $creator->eligiblePartners(),
            // L'ordine del menù è quello del service, non un terzo elenco.
            'creatableFamilies' => AdminServiceCreator::CREATABLE_FAMILIES,
        ])
            ->layout('layouts::admin')
            ->title(__('admin-catalog.index.title'));
    }
}
