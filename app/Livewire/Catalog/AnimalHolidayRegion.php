<?php

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHolidayRegion extends Component
{
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
        // TODO: filter structures once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.catalog.animal-holiday-region', [
            // Il mock XD mostra gli stessi 12 risultati per ogni regione: il filtro
            // per regione arriva col motore di ricerca (step 6).
            'results' => Structure::orderBy('position')->get(),
        ])->title('AnimalAmo — '.__('catalog.region_title', ['region' => $this->regionName]));
    }
}
