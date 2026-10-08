<?php

namespace App\Livewire\Catalog;

use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Livewire\Concerns\AddsCatalogProductToCart;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Region\Region;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Services\Partner\PartnerContacts;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class AnimalHolidayStructure extends Component
{
    use AddsCatalogProductToCart;
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

    /**
     * Stanza scelta (?camera=id): null per le strutture senza stanze. Arriva
     * dal client: ogni lettura passa da selectedRoom(), che rifiuta le stanze
     * di altre strutture e ripiega sulla stanza proposta.
     */
    #[Url(as: 'camera')]
    public ?int $roomId = null;

    /** Recensioni mostrate: parte da 3 (XD), cresce a step di 3 con "Carica altre recensioni". */
    public int $reviewsShown = 3;

    /** Campi espandibili ammessi nel widget. */
    public const FIELDS = ['date', 'ospiti', 'animali'];

    /** Step di paginazione incrementale delle recensioni. */
    private const REVIEWS_STEP = 3;

    /**
     * Stanze risolte in questa richiesta, per roomId (calendario, stepper e
     * render la chiedono più volte; null = struttura senza stanze).
     *
     * @var array<string, Room|null>
     */
    private array $resolvedRooms = [];

    public function mount(string $region, string $structure): void
    {
        $regionModel = Region::where('slug', $region)->first();

        abort_unless($regionModel !== null, 404);

        $model = self::findBySlug($structure);

        if ($model === null) {
            self::redirectIfMerged($structure);
        }

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

        // Stanza: quella dell'URL se è di questa struttura, altrimenti la prima libera nelle date di default.
        $this->roomId = $this->selectedRoom($model)?->id;
        $this->clampToRoom();
    }

    /**
     * «Seleziona» di una card stanza: le stanze di altre strutture si ignorano.
     * Ospiti e animali rientrano nella capienza della nuova stanza.
     */
    public function selectRoom(int $id): void
    {
        $room = $this->structure()->rooms()->whereKey($id)->first();

        if ($room === null) {
            return;
        }

        $this->roomId = $room->id;
        $this->resolvedRooms[(string) $room->id] = $room;
        $this->clampToRoom();
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
        // Titolare che incassa in struttura (difetto C7, 28/09/2026): la CTA non
        // c'è, ma il metodo arriva dal payload del client — lo rifiuta il trait.
        if (! $this->addCatalogProductToCart($this->structure(), $this->bookingOptions())) {
            return;
        }

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
        $this->reviewsShown = min($this->reviewsShown + self::REVIEWS_STEP, $this->structure()->reviews->count());
    }

    public function render()
    {
        $structure = $this->structure();
        $nights = $this->nights();
        $room = $this->selectedRoom($structure);
        // Prezzo notte: della stanza scelta, della struttura se non ha stanze.
        $nightCents = $room?->price_cents ?? $structure->price_cents;
        // Sezione «Scegli la camera» solo con almeno due stanze: con una sola la stanza è implicita.
        $rooms = $structure->rooms()->with('amenities')->get();

        return view('livewire.catalog.animal-holiday-structure', [
            'structure' => $structure,
            // «Vedere tutte le foto»: vuoto con una foto sola, e allora niente pulsante né modale.
            'galleryPhotos' => $structure->galleryPhotos(),
            'isFav' => $this->isFavorite('structure', $structure->id),
            'hotelServices' => $structure->amenityRows('hotel'),
            'animalServices' => $structure->amenityRows('animal'),
            'faqs' => $structure->faqs,
            'reviews' => $structure->reviews->take($this->reviewsShown),
            'reviewsCount' => $structure->reviews->count(),
            // Preventivo live: notti reali, supplemento animali (riga solo se > 0) e totale quotato server-side.
            'nights' => $nights,
            'room' => $rooms->count() > 1 ? $room : null,
            'rooms' => $rooms->count() > 1 ? $rooms : collect(),
            'nightCents' => $nightCents,
            'nightsCents' => $nightCents * $nights,
            'animalSupplementCents' => $structure->animal_supplement_cents * array_sum($this->editAnimals) * $nights,
            'totalCents' => app(BookingPricingService::class)->quote($structure, $this->bookingOptions()),
            'calendar' => $this->expandedField === 'date' ? $this->buildCalendar() : [],
            'calendarLabel' => $this->calendarLabel(),
            'guestsAtMax' => $this->guestsAtMax(),
            'animalsAtMax' => $this->animalsAtMax(),
            // Service memoizzato per user_id: una query per richiesta, anche se
            // findBySlug gira più volte (render + calendario).
            'paysOnSite' => app(PartnerPaymentModeService::class)->forPurchasable($structure) === OrderPaymentMode::OnSite,
            // Card contatti al posto del box prenotazione quando il partner non vende online.
            'contacts' => app(PartnerContacts::class)->forPurchasable($structure),
        ])->title('AnimalAmo — '.$structure->name);
    }

    /** Struttura del calendario del widget: i suoi giorni chiusi sono disabilitati. */
    protected function calendarStructure(): ?Structure
    {
        return self::findBySlug($this->structureSlug);
    }

    /** Stanza del calendario e degli stepper: quella scelta. */
    protected function calendarRoom(): ?Room
    {
        return $this->selectedRoom();
    }

    /**
     * Stanza scelta: roomId se appartiene alla struttura, altrimenti la stanza
     * proposta per le date correnti (Structure::defaultRoomFor); null se la
     * struttura non ha stanze.
     */
    private function selectedRoom(?Structure $structure = null): ?Room
    {
        $key = (string) $this->roomId;

        if (array_key_exists($key, $this->resolvedRooms)) {
            return $this->resolvedRooms[$key];
        }

        $structure ??= $this->structure();

        $room = ($this->roomId !== null ? $structure->rooms()->whereKey($this->roomId)->first() : null)
            ?? $structure->defaultRoomFor(
                CarbonImmutable::instance(self::parseDate($this->editCheckIn ?? '')),
                CarbonImmutable::instance(self::parseDate($this->editCheckOut ?? $this->editCheckIn ?? '')),
            );

        return $this->resolvedRooms[$key] = $room;
    }

    /**
     * Riporta ospiti e animali entro la capienza della stanza scelta: si tolgono
     * prima bambini e ragazzi, gli adulti restano almeno 1; gli animali si
     * tolgono dall'ultima specie.
     */
    private function clampToRoom(): void
    {
        $room = $this->selectedRoom();

        if ($room === null) {
            return;
        }

        foreach (['bambini', 'ragazzi', 'adulti'] as $key) {
            $min = $key === 'adulti' ? 1 : 0;

            while (array_sum($this->editGuests) > $room->max_guests && ($this->editGuests[$key] ?? 0) > $min) {
                $this->editGuests[$key]--;
            }
        }

        foreach (array_reverse(array_keys($this->editAnimals)) as $species) {
            while (array_sum($this->editAnimals) > $room->max_animals && $this->editAnimals[$species] > 0) {
                $this->editAnimals[$species]--;
            }
        }
    }

    /** Struttura della pagina (rirrisolta dallo slug a ogni richiesta, come il render). */
    private function structure(): Structure
    {
        $structure = self::findBySlug($this->structureSlug);

        abort_unless($structure !== null, 404);

        return $structure;
    }

    /** Options del widget nel vocabolario canonico del carrello (decisione ratificata #4), più la stanza se la struttura ne ha. */
    private function bookingOptions(): array
    {
        $options = [
            'check_in' => self::parseDate($this->editCheckIn ?? '')->format('Y-m-d'),
            // Intervallo lasciato a metà: check-out = check-in (la validazione server segnala il range non valido).
            'check_out' => self::parseDate($this->editCheckOut ?? $this->editCheckIn ?? '')->format('Y-m-d'),
            'guests' => $this->editGuests,
            'animals' => $this->editAnimals,
        ];

        $room = $this->selectedRoom();

        if ($room !== null) {
            $options['room_id'] = $room->id;
        }

        return $options;
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

    /**
     * Struttura accorpata in un'altra (`catalog:merge-structures`): la vecchia
     * URL, magari indicizzata o condivisa, risponde 301 verso la scheda che
     * ora la contiene come stanza. Una catena di accorpamenti si segue per
     * pochi passi, mai in cerchio; se il target non è visibile resta il 404.
     */
    private static function redirectIfMerged(string $slug): void
    {
        $merged = Structure::withHidden()->where('slug', $slug)->whereNotNull('merged_into_structure_id')->first();
        $seen = [];

        while ($merged !== null && $merged->merged_into_structure_id !== null && count($seen) < 3) {
            $seen[] = $merged->id;
            $merged = in_array((int) $merged->merged_into_structure_id, $seen, true)
                ? null
                : Structure::withHidden()->with('region')->find($merged->merged_into_structure_id);
        }

        if ($merged === null || $merged->merged_into_structure_id !== null || ! $merged->isVisibleInCatalog() || $merged->region === null) {
            return;
        }

        throw new HttpResponseException(redirect()->route('holiday.structure', [
            'region' => $merged->region->slug,
            'structure' => $merged->slug,
        ], 301));
    }
}
