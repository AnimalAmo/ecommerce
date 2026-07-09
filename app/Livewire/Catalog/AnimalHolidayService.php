<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Region\Region;
use App\Models\Structure\Structure;
use App\Services\Cart\CartManager;
use App\Services\Pricing\BookingPricingService;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AnimalHolidayService extends Component
{
    use HasBookingCalendar;
    use TogglesFavorites;

    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug servizio dalla rotta (es. "dog-sitting"). */
    public string $serviceSlug = '';

    /** Pop-up "Aggiunto al carrello" (stesso pattern del dettaglio struttura). */
    public bool $cartPopupOpen = false;

    /** Orari del servizio (whitelist 08:00–20:00; default 10:00 → 16:00 come da XD). */
    public string $editTimeFrom = '10:00';

    public string $editTimeTo = '16:00';

    /** Campo espanso del widget prenotazione: null | 'date' | 'orari' | 'animali' (uno alla volta, come il pop-up del carrello). */
    public ?string $expandedField = null;

    /** Recensioni mostrate: parte da 3 (XD), cresce a step di 3 con "Carica altre recensioni". */
    public int $reviewsShown = 3;

    /** Campi espandibili ammessi nel widget ('date' = giorno singolo del servizio). */
    public const FIELDS = ['date', 'orari', 'animali'];

    /** Step di paginazione incrementale delle recensioni. */
    private const REVIEWS_STEP = 3;

    public function mount(string $region, string $service): void
    {
        $regionModel = Region::where('slug', $region)->first();

        abort_unless($regionModel !== null, 404);

        abort_unless(self::findBySlug($service) !== null, 404);

        $this->regionSlug = $regionModel->slug;
        $this->regionName = $regionModel->name;
        $this->serviceSlug = $service;

        // Default del widget: giorno oggi+7, orario 10:00–16:00, 1 animale; il calendario seleziona un giorno singolo.
        $this->calendarSingleDay = true;
        $this->editCheckIn = (new DateTimeImmutable('today'))->modify('+7 days')->format('d/m/Y');
        $this->editAnimals = [self::defaultSpecies() => 1];
    }

    /** Apre/chiude un campo del widget; aprire il calendario lo ripunta al mese del giorno scelto. */
    public function toggleField(string $field): void
    {
        if (! in_array($field, self::FIELDS, true)) {
            return;
        }

        $this->expandedField = $this->expandedField === $field ? null : $field;

        if ($this->expandedField === 'date' && $this->editCheckIn !== null) {
            $this->pointCalendarAt(self::parseDate($this->editCheckIn));
        }
    }

    /**
     * CTA del widget: aggiunge la prenotazione al carrello (anche da guest:
     * carrello in sessione, nessun gate di login) e apre il pop-up di conferma;
     * violazione disponibilità/orari = toast danger e nessuna riga aggiunta.
     */
    public function addToCart(): void
    {
        try {
            app(CartManager::class)->addItem('structure', $this->service()->id, $this->bookingOptions(), false);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
        $this->expandedField = null;
        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    /** Mostra il blocco successivo di recensioni (step di 3), con clamp al totale disponibile. */
    public function loadMoreReviews(): void
    {
        $this->reviewsShown = min($this->reviewsShown + self::REVIEWS_STEP, $this->service()->reviews->count());
    }

    public function render()
    {
        $service = $this->service();
        $hours = $this->hours();

        return view('livewire.catalog.animal-holiday-service', [
            'service' => $service,
            // I servizi sono righe Structure: alias morph 'structure'.
            'isFav' => $this->isFavorite('structure', $service->id),
            'animalServices' => $service->amenityRows('animal'),
            'faqs' => $service->faqs,
            'reviews' => $service->reviews->take($this->reviewsShown),
            'reviewsCount' => $service->reviews->count(),
            // Preventivo live: ore reali dall'intervallo scelto, supplemento animali (riga solo se > 0), totale server-side.
            'hours' => $hours,
            'hoursCents' => $service->price_cents * $hours,
            'animalSupplementCents' => $service->animal_supplement_cents * array_sum($this->editAnimals) * $hours,
            'totalCents' => app(BookingPricingService::class)->quote($service, $this->bookingOptions()),
            'calendar' => $this->expandedField === 'date' ? $this->buildCalendar() : [],
            'calendarLabel' => $this->calendarLabel(),
            'animalsAtMax' => $this->animalsAtMax(),
            'bookingHours' => self::bookingHours(),
        ])->title('AnimalAmo — '.$service->name);
    }

    /** Struttura del calendario del widget: i giorni chiusi del servizio sono disabilitati. */
    protected function calendarStructure(): ?Structure
    {
        return self::findBySlug($this->serviceSlug);
    }

    /** Servizio della pagina (ririsolto dallo slug a ogni richiesta, come il render). */
    private function service(): Structure
    {
        $service = self::findBySlug($this->serviceSlug);

        abort_unless($service !== null, 404);

        return $service;
    }

    /** Options del widget nel vocabolario canonico del carrello (niente guests: il servizio è a ore). */
    private function bookingOptions(): array
    {
        [$timeFrom, $timeTo] = $this->times();

        return [
            'animals' => $this->editAnimals,
            'day' => self::parseDate($this->editCheckIn ?? '')->format('Y-m-d'),
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ];
    }

    /**
     * Coppia dalle/alle sanitizzata sulla whitelist oraria (valori manomessi
     * riportati ai default XD): il pricing e la disponibilità ricevono sempre 'HH:00'.
     *
     * @return array{0: string, 1: string}
     */
    private function times(): array
    {
        $hours = self::bookingHours();

        return [
            in_array($this->editTimeFrom, $hours, true) ? $this->editTimeFrom : '10:00',
            in_array($this->editTimeTo, $hours, true) ? $this->editTimeTo : '16:00',
        ];
    }

    /** Ore del preventivo live (minimo 1, stesso clamp del pricing server; 10:00 → 16:00 = 6 ore). */
    private function hours(): int
    {
        [$timeFrom, $timeTo] = $this->times();

        return max(1, (int) substr($timeTo, 0, 2) - (int) substr($timeFrom, 0, 2));
    }

    /** Specie preselezionata dello stepper animali: il primo pet dell'utente autenticato, altrimenti 'cane'. */
    private static function defaultSpecies(): string
    {
        $species = mb_strtolower(trim((string) Auth::user()?->pets()->first()?->species));

        return $species !== '' ? $species : 'cane';
    }

    private static function findBySlug(string $slug): ?Structure
    {
        return Structure::where('slug', $slug)
            ->where('type', ProductType::Service)
            ->orderBy('position')
            ->first();
    }
}
