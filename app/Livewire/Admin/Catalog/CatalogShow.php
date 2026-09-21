<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Concerns\ConfirmsCatalogActions;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CatalogShow extends Component
{
    use ConfirmsCatalogActions;

    #[Locked]
    public string $type = '';

    #[Locked]
    public int $itemId = 0;

    /** Scheda di lingua dei testi: it | en. */
    public string $lang = 'it';

    /** @var array<string, string> */
    public array $name = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['it' => '', 'en' => ''];

    /** Euro, come li scrive la cliente ("120" o "120,50"). */
    public string $price = '';

    public string $supplement = '';

    public string $regionId = '';

    public string $cancellationDays = '';

    public function mount(string $type, int $id, CatalogAdmin $catalog): void
    {
        $this->type = $type;
        $this->itemId = $id;

        $this->fillForm($catalog->find($type, $id), $catalog);
    }

    public function save(CatalogAdmin $catalog): void
    {
        $item = $this->item($catalog);
        $isStructure = $item instanceof Structure;

        $this->validate([
            'name.it' => ['required', 'string', 'max:160'],
            'name.en' => ['nullable', 'string', 'max:160'],
            'description.it' => ['required', 'string', 'max:10000'],
            'description.en' => ['nullable', 'string', 'max:10000'],
            'price' => ['nullable', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'],
            'supplement' => [$isStructure ? 'nullable' : 'exclude', 'regex:/^\d{1,5}([.,]\d{1,2})?$/'],
            'regionId' => [$isStructure ? 'nullable' : 'exclude', 'exists:regions,id'],
            'cancellationDays' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [
            'name.it.required' => __('admin-catalog.validation.name_required'),
            'description.it.required' => __('admin-catalog.validation.description_required'),
            'price.regex' => __('admin-catalog.validation.price_format'),
            'supplement.regex' => __('admin-catalog.validation.supplement_format'),
        ]);

        $catalog->update($item, [
            'name' => $this->name,
            'description' => $this->description,
            'price_cents' => $this->cents($this->price),
            'supplement_cents' => $this->cents($this->supplement),
            'region_id' => $this->regionId !== '' ? (int) $this->regionId : null,
            'cancellation_policy_days' => $this->cancellationDays !== '' ? (int) $this->cancellationDays : null,
        ]);

        Flux::toast(text: __('admin-catalog.show.saved'), variant: 'success');
    }

    protected function afterCatalogAction(bool $deleted): void
    {
        if ($deleted) {
            $this->redirectRoute('admin.catalog.index', navigate: true);
        }
    }

    public function render(CatalogAdmin $catalog, CatalogPresenter $presenter)
    {
        $item = $this->item($catalog);
        $bookings = $catalog->bookings($item);
        $rating = $catalog->averageRating($item);

        return view('livewire.admin.catalog.show', [
            'row' => $presenter->row($item),
            'item' => $item,
            'isStructure' => $item instanceof Structure,
            'isEvent' => $item->getMorphClass() === 'event',
            'publicUrl' => $item->isVisibleInCatalog() ? $presenter->publicUrl($item) : null,
            'publishedOn' => ($item->approved_at ?? $item->created_at)?->locale('it')->isoFormat('D MMMM YYYY'),
            'stats' => [
                ['label' => __('admin-catalog.show.stats.bookings'), 'value' => (string) $bookings['total']],
                ['label' => __('admin-catalog.show.stats.future'), 'value' => (string) $bookings['future']],
                ['label' => __('admin-catalog.show.stats.favorites'), 'value' => (string) $catalog->favoritesCount($item)],
                ['label' => __('admin-catalog.show.stats.rating'), 'value' => $rating !== null ? Format::rating($rating) : __('admin.none')],
            ],
            'blocker' => $catalog->deletionBlocker($item),
            'regions' => Region::query()->orderBy('name')->pluck('name', 'id'),
        ])
            ->layout('layouts::admin')
            ->title($catalog->name($item));
    }

    private function item(CatalogAdmin $catalog): Model
    {
        return $catalog->find($this->type, $this->itemId);
    }

    private function fillForm(Model $item, CatalogAdmin $catalog): void
    {
        $column = CatalogAdmin::FAMILIES[$this->type][2];

        foreach (['it', 'en'] as $locale) {
            $this->name[$locale] = (string) $item->getTranslation($column, $locale, false);
            $this->description[$locale] = (string) $item->getTranslation('description', $locale, false);
        }

        $this->price = $this->euros($item->price_cents);
        $this->supplement = $item instanceof Structure ? $this->euros($item->animal_supplement_cents) : '';
        $this->regionId = $item instanceof Structure ? (string) ($item->region_id ?? '') : '';
        $this->cancellationDays = (string) ($item->cancellation_policy_days ?? '');
    }

    private function euros(?int $cents): string
    {
        if ($cents === null || $cents === 0) {
            return '';
        }

        return $cents % 100 === 0
            ? (string) intdiv($cents, 100)
            : number_format($cents / 100, 2, ',', '');
    }

    private function cents(string $euros): ?int
    {
        $euros = trim($euros);

        return $euros === '' ? null : (int) round(((float) str_replace(',', '.', $euros)) * 100);
    }
}
