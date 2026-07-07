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
        // La ricerca "Dove" filtra le strutture in render(). Quando/ospiti/animali
        // non filtrano ancora (step 6 disponibilità).
    }

    public function render()
    {
        $term = trim($this->where);

        // Il mock XD mostra gli stessi 12 risultati per ogni regione: se "Dove" è vuoto o
        // coincide col nome della regione (pre-compilato in mount) manteniamo quel comportamento;
        // se l'utente cambia il testo, filtriamo per nome OR location (LIKE %dove%, case-insensitive).
        $showAll = $term === '' || mb_strtolower($term) === mb_strtolower($this->regionName);

        return view('livewire.catalog.animal-holiday-region', [
            'results' => Structure::query()
                ->when(! $showAll, function ($query) use ($term): void {
                    $like = '%'.addcslashes($term, '\%_').'%';
                    $query->where(fn ($sub) => $sub->whereLike('name', $like)->orWhereLike('location', $like));
                })
                ->orderBy('position')
                ->get(),
        ])->title('AnimalAmo — '.__('catalog.region_title', ['region' => $this->regionName]));
    }
}
