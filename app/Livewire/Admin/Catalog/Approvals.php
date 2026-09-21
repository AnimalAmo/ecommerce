<?php

namespace App\Livewire\Admin\Catalog;

use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class Approvals extends Component
{
    /** Scheda a cui si stanno chiedendo modifiche: [family, id, name]. */
    public ?array $changing = null;

    public string $note = '';

    public function approve(string $family, int $id, CatalogAdmin $catalog): void
    {
        $item = $catalog->find($family, $id);
        $catalog->approve($item);

        Flux::toast(text: __('admin-catalog.approvals.approved', ['name' => $catalog->name($item)]), variant: 'success');
    }

    public function askChanges(string $family, int $id, CatalogAdmin $catalog): void
    {
        $this->changing = ['family' => $family, 'id' => $id, 'name' => $catalog->name($catalog->find($family, $id))];
        $this->note = '';
        $this->resetValidation();

        Flux::modal('request-changes')->show();
    }

    public function requestChanges(CatalogAdmin $catalog): void
    {
        $this->validate(
            ['note' => ['required', 'string', 'min:10', 'max:2000']],
            [
                'note.required' => __('admin-catalog.validation.note_required'),
                'note.min' => __('admin-catalog.validation.note_min'),
            ],
        );

        $item = $catalog->find($this->changing['family'], $this->changing['id']);
        $catalog->requestChanges($item, trim($this->note));

        Flux::modal('request-changes')->close();
        Flux::toast(text: __('admin-catalog.approvals.changes_sent'), variant: 'success');

        $this->reset('changing', 'note');
    }

    public function render(CatalogAdmin $catalog, CatalogPresenter $presenter)
    {
        $pending = $catalog->pendingApprovals();

        return view('livewire.admin.catalog.approvals', [
            'cards' => $pending->map(fn (Model $item) => $presenter->row($item) + [
                'description' => str((string) $item->getTranslation('description', 'it', false))->stripTags()->limit(320)->toString(),
                'when' => ($item->approval_requested_at ?? $item->created_at)?->locale('it')->diffForHumans(),
                'facts' => $this->facts($item, $presenter),
            ]),
            'moderation' => (bool) config('admin.moderation'),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-catalog.approvals.title'));
    }

    /** @return list<array{label: string, value: string}> */
    private function facts(Model $item, CatalogPresenter $presenter): array
    {
        $facts = [['label' => __('admin-catalog.approvals.facts.price'), 'value' => $presenter->price($item)]];

        if (isset($item->animal_supplement_cents) && $item->animal_supplement_cents > 0) {
            $facts[] = ['label' => __('admin-catalog.approvals.facts.supplement'), 'value' => Format::money((int) $item->animal_supplement_cents)];
        }

        if (isset($item->validity_months) && $item->validity_months) {
            $facts[] = ['label' => __('admin-catalog.approvals.facts.validity'), 'value' => trans_choice('admin-catalog.approvals.facts.validity_value', (int) $item->validity_months)];
        }

        if (isset($item->cancellation_policy_days) && $item->cancellation_policy_days !== null) {
            $facts[] = ['label' => __('admin-catalog.approvals.facts.cancellation'), 'value' => trans_choice('admin-catalog.approvals.facts.cancellation_value', (int) $item->cancellation_policy_days)];
        }

        return $facts;
    }
}
