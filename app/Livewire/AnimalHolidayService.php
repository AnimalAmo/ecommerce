<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Dettaglio servizio')]
class AnimalHolidayService extends Component
{
    use TogglesFavorites;

    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug servizio dalla rotta (es. "dog-sitting"). */
    public string $serviceSlug = '';

    /** Pop-up "Aggiunto al carrello" (stesso pattern del dettaglio struttura). */
    public bool $cartPopupOpen = false;

    /** Ore del preventivo campione (10:00 → 16:00 come da XD); dinamiche con il carrello (step 3). */
    public const SAMPLE_HOURS = 4;

    public function mount(string $region, string $service): void
    {
        $regionModel = Region::where('slug', $region)->first();

        abort_unless($regionModel !== null, 404);

        abort_unless(self::findBySlug($service) !== null, 404);

        $this->regionSlug = $regionModel->slug;
        $this->regionName = $regionModel->name;
        $this->serviceSlug = $service;
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
        return Structure::where('slug', $slug)
            ->where('type', ProductType::Service)
            ->orderBy('position')
            ->first();
    }

    public function render()
    {
        $service = self::findBySlug($this->serviceSlug);

        abort_unless($service !== null, 404);

        return view('livewire.animal-holiday-service', [
            'service' => $service,
            // I servizi sono righe Structure: alias morph 'structure'.
            'isFav' => $this->isFavorite('structure', $service->id),
            'animalServices' => $service->amenityRows('animal'),
            'faqs' => $service->faqs,
            'reviews' => $service->reviews->take(3),
            'reviewsCount' => $service->reviews->count(),
            'hours' => self::SAMPLE_HOURS,
        ])->title('AnimalAmo — '.$service->name);
    }
}
