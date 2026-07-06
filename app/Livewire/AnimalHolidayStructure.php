<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Dettaglio struttura')]
class AnimalHolidayStructure extends Component
{
    use TogglesFavorites;

    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug struttura dalla rotta (es. "hotel-brescia"); non unico nel mock, vince la prima per posizione. */
    public string $structureSlug = '';

    /** Pop-up "Aggiunto al carrello" (XD: "Pop-up aggiunta al carrello"). */
    public bool $cartPopupOpen = false;

    /** Notti del preventivo campione (check-in 17/12 → check-out 22/12 come da XD); dinamiche con il carrello (step 3). */
    public const SAMPLE_NIGHTS = 5;

    public function mount(string $region, string $structure): void
    {
        $regionModel = Region::where('slug', $region)->first();

        abort_unless($regionModel !== null, 404);

        $model = self::findBySlug($structure);

        abort_unless($model !== null, 404);

        // I risultati di tipo servizio hanno una scheda dedicata.
        if ($model->type === ProductType::Service) {
            $this->redirectRoute('holiday.service', ['region' => $region, 'service' => $structure]);

            return;
        }

        $this->regionSlug = $regionModel->slug;
        $this->regionName = $regionModel->name;
        $this->structureSlug = $structure;
    }

    public function addToCart(): void
    {
        // TODO: carrello reale — per ora mostra solo il pop-up di conferma.
        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function loadMoreReviews(): void
    {
        // TODO: paginare le recensioni quando il design definirà la pagina 2.
    }

    private static function findBySlug(string $slug): ?Structure
    {
        return Structure::where('slug', $slug)->orderBy('position')->first();
    }

    public function render()
    {
        $structure = self::findBySlug($this->structureSlug);

        abort_unless($structure !== null, 404);

        return view('livewire.animal-holiday-structure', [
            'structure' => $structure,
            'isFav' => $this->isFavorite('structure', $structure->id),
            'hotelServices' => $structure->amenityRows('hotel'),
            'animalServices' => $structure->amenityRows('animal'),
            'faqs' => $structure->faqs,
            'reviews' => $structure->reviews->take(3),
            'reviewsCount' => $structure->reviews->count(),
            'nights' => self::SAMPLE_NIGHTS,
        ])->title('AnimalAmo — '.$structure->name);
    }
}
