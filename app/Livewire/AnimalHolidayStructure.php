<?php

namespace App\Livewire;

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
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Dettaglio struttura')]
class AnimalHolidayStructure extends Component
{
    use HasBookingCalendar;
    use TogglesFavorites;

    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug struttura dalla rotta (es. "hotel-brescia"); non unico nel mock, vince la prima per posizione. */
    public string $structureSlug = '';

    /** Pop-up "Aggiunto al carrello" (XD: "Pop-up aggiunta al carrello"). */
    public bool $cartPopupOpen = false;

    /** Campo espanso del widget prenotazione: null | 'date' | 'ospiti' | 'animali' (uno alla volta, come il pop-up del carrello). */
    public ?string $expandedField = null;

    /** Campi espandibili ammessi nel widget. */
    public const FIELDS = ['date', 'ospiti', 'animali'];

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

        // Default del widget: oggi+7 → oggi+12 (5 notti: al primo load replica il preventivo dell'XD), 2 adulti, 1 animale.
        $today = new DateTimeImmutable('today');
        $this->editCheckIn = $today->modify('+7 days')->format('d/m/Y');
        $this->editCheckOut = $today->modify('+12 days')->format('d/m/Y');
        $this->editGuests = ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0];
        $this->editAnimals = [self::defaultSpecies() => 1];
    }

    /** Apre/chiude un campo del widget; aprire il calendario lo ripunta al mese del check-in. */
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
     * violazione disponibilità = toast danger e nessuna riga aggiunta.
     */
    public function addToCart(): void
    {
        try {
            app(CartManager::class)->addItem('structure', $this->structure()->id, $this->bookingOptions(), false);
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

    public function loadMoreReviews(): void
    {
        // TODO: paginare le recensioni quando il design definirà la pagina 2.
    }

    public function render()
    {
        $structure = $this->structure();
        $nights = $this->nights();

        return view('livewire.animal-holiday-structure', [
            'structure' => $structure,
            'isFav' => $this->isFavorite('structure', $structure->id),
            'hotelServices' => $structure->amenityRows('hotel'),
            'animalServices' => $structure->amenityRows('animal'),
            'faqs' => $structure->faqs,
            'reviews' => $structure->reviews->take(3),
            'reviewsCount' => $structure->reviews->count(),
            // Preventivo live: notti reali, supplemento animali (riga solo se > 0) e totale quotato server-side.
            'nights' => $nights,
            'nightsCents' => $structure->price_cents * $nights,
            'animalSupplementCents' => $structure->animal_supplement_cents * array_sum($this->editAnimals) * $nights,
            'totalCents' => app(BookingPricingService::class)->quote($structure, $this->bookingOptions()),
            'calendar' => $this->expandedField === 'date' ? $this->buildCalendar() : [],
            'calendarLabel' => $this->calendarLabel(),
            'guestsAtMax' => $this->guestsAtMax(),
            'animalsAtMax' => $this->animalsAtMax(),
        ])->title('AnimalAmo — '.$structure->name);
    }

    /** Struttura del calendario del widget: i suoi giorni chiusi sono disabilitati. */
    protected function calendarStructure(): ?Structure
    {
        return self::findBySlug($this->structureSlug);
    }

    /** Struttura della pagina (rirrisolta dallo slug a ogni richiesta, come il render). */
    private function structure(): Structure
    {
        $structure = self::findBySlug($this->structureSlug);

        abort_unless($structure !== null, 404);

        return $structure;
    }

    /** Options del widget nel vocabolario canonico del carrello (decisione ratificata #4). */
    private function bookingOptions(): array
    {
        return [
            'check_in' => self::parseDate($this->editCheckIn ?? '')->format('Y-m-d'),
            // Intervallo lasciato a metà: check-out = check-in (la validazione server segnala il range non valido).
            'check_out' => self::parseDate($this->editCheckOut ?? $this->editCheckIn ?? '')->format('Y-m-d'),
            'guests' => $this->editGuests,
            'animals' => $this->editAnimals,
        ];
    }

    /** Notti del preventivo live (minimo 1, stesso clamp del pricing server). */
    private function nights(): int
    {
        $checkIn = self::parseDate($this->editCheckIn ?? '');
        $checkOut = self::parseDate($this->editCheckOut ?? $this->editCheckIn ?? '');

        return max(1, (int) $checkIn->diff($checkOut)->days);
    }

    /** Specie preselezionata dello stepper animali: il primo pet dell'utente autenticato, altrimenti 'cane'. */
    private static function defaultSpecies(): string
    {
        $species = mb_strtolower(trim((string) Auth::user()?->pets()->first()?->species));

        return $species !== '' ? $species : 'cane';
    }

    private static function findBySlug(string $slug): ?Structure
    {
        return Structure::where('slug', $slug)->orderBy('position')->first();
    }
}
