<?php

namespace App\Livewire;

use DateTimeImmutable;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;

class Cart extends Component
{
    /** Flag ?regalo=1 (deep-link come ?tab della community): carica gli articoli regalo al posto dei normali. */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Articoli nel carrello in-memory (come le pagine sorelle); niente DB. */
    public array $items = [];

    /** Dedica e messaggio della smartbox regalo, per id articolo. */
    // TODO: persistenza regalo backend — per ora restano solo nello stato del componente.
    public array $giftDedication = [];

    public array $giftMessage = [];

    /** Id delle card suggerite (stato vuoto) marcate preferite / aggiunte al carrello (solo visivo). */
    public array $suggestFavorites = [];

    public array $suggestInCart = [];

    /** Id dell'articolo in modifica nel pop-up (null = pop-up chiuso). */
    public ?int $editingId = null;

    /** Copie di lavoro del pop-up (Annulla le scarta, Conferma le riversa nell'articolo). */
    public ?string $editCheckIn = null;

    public ?string $editCheckOut = null;

    /** @var array{adulti: int, ragazzi: int, bambini: int} */
    public array $editGuests = ['adulti' => 1, 'ragazzi' => 0, 'bambini' => 0];

    public int $editDogs = 0;

    /** Campo espanso nel pop-up: null | 'date' | 'ospiti' | 'animali' (uno alla volta). */
    public ?string $expandedField = null;

    /** Mese (1-12) e anno mostrati dal calendario inline del pop-up. */
    public int $calendarMonth = 1;

    public int $calendarYear = 2024;

    /** Campi espandibili ammessi nel pop-up. */
    public const FIELDS = ['date', 'ospiti', 'animali'];

    /** Nomi dei mesi per il titolo del calendario ("Marzo 2024"). */
    public const MONTHS = [
        1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
    ];

    /**
     * Articoli campione come da XD (artboard "Carrello", dall'alto in basso);
     * statici come nelle pagine sorelle, struttura pronta per un backend reale.
     * Le date sono dd/mm/yyyy; 'dates' null = articolo senza riga date (item 3).
     */
    public const ITEMS = [
        [
            'id' => 1,
            'tag' => 'Struttura',
            'tagColor' => '#FF9F3E',
            'title' => 'Hotel Brescia',
            'location' => 'Dario Boario Terme (BS), Italia',
            'dates' => ['checkIn' => '17/02/2024', 'checkOut' => '22/02/2024'],
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'dogs' => 1,
            'price' => 215,
            'photo' => 'cart-hotel-brescia.jpg',
        ],
        [
            'id' => 2,
            'tag' => 'Attività',
            'tagColor' => '#8E53E6',
            'title' => 'Weekend di escursioni',
            'location' => 'Viareggio, Italia',
            'dates' => ['checkIn' => '21/05/2024', 'checkOut' => '23/05/2024'],
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'dogs' => 1,
            'price' => 118,
            'photo' => 'cart-excursions-viareggio.jpg',
        ],
        [
            'id' => 3,
            'tag' => 'Soggiorno',
            'tagColor' => '#8DE0FF',
            'title' => 'Weekend in Piemonte',
            'location' => 'Torino, Italia',
            'dates' => null,
            'guests' => ['adulti' => 4, 'ragazzi' => 0, 'bambini' => 0],
            'dogs' => 2,
            'price' => 143,
            'photo' => 'cart-weekend-piemonte.jpg',
        ],
    ];

    /**
     * Articolo del flusso regalo smartbox (artboard "Carrello – flusso regalo smartbox").
     * NOTA: il chip dice "Struttura" anche se l'articolo è una smartbox — copiato
     * VERBATIM dal mock XD per fedeltà, l'incongruenza è voluta dal design.
     */
    public const GIFT_ITEMS = [
        [
            'id' => 1,
            'tag' => 'Struttura',
            'tagColor' => '#FF9F3E',
            'title' => 'Weekend in Piemonte',
            'location' => 'Torino, Italia',
            'dates' => null,
            'guests' => ['adulti' => 4, 'ragazzi' => 0, 'bambini' => 0],
            'dogs' => 2,
            'price' => 143,
            'photo' => 'cart-weekend-piemonte.jpg',
            'gift' => true,
            'giftValidity' => 'Smartbox valida per 12 mesi',
        ],
    ];

    /** Dedica e messaggio di default del regalo come da mock XD (fallback se i campi restano vuoti). */
    public const GIFT_DEDICATION_DEFAULT = 'Sofia';

    public const GIFT_MESSAGE_DEFAULT = 'ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat';

    public function mount(): void
    {
        $this->items = $this->gift ? self::GIFT_ITEMS : self::ITEMS;
    }

    /** CTA "Vai al checkout" in modalità regalo: passa dedica/messaggio al checkout via sessione. */
    public function goToCheckout()
    {
        // TODO: persistenza regalo backend — per ora la dedica viaggia in sessione fino al checkout.
        $id = self::GIFT_ITEMS[0]['id'];

        $dedication = trim($this->giftDedication[$id] ?? '');
        $message = trim($this->giftMessage[$id] ?? '');

        session()->put('giftCheckout', [
            'dedication' => $dedication !== '' ? $dedication : self::GIFT_DEDICATION_DEFAULT,
            'message' => $message !== '' ? $message : self::GIFT_MESSAGE_DEFAULT,
        ]);

        return $this->redirectRoute('checkout', ['regalo' => 1]);
    }

    /** Cuore sulle card suggerite dello stato vuoto: parte bianco e diventa giallo (toggle). */
    public function toggleSuggestionFavorite(int $id): void
    {
        // TODO: backend reale — stato solo visivo, come il toggle borsa dei preferiti.
        if (in_array($id, $this->suggestFavorites, true)) {
            $this->suggestFavorites = array_values(array_diff($this->suggestFavorites, [$id]));
        } else {
            $this->suggestFavorites[] = $id;
        }
    }

    /** Borsa sulle card suggerite dello stato vuoto: aggiunge/toglie dal carrello (solo visivo). */
    public function toggleSuggestionCart(int $id): void
    {
        // TODO: backend reale.
        if (in_array($id, $this->suggestInCart, true)) {
            $this->suggestInCart = array_values(array_diff($this->suggestInCart, [$id]));
        } else {
            $this->suggestInCart[] = $id;
        }
    }

    /** Il bottone "Elimina" rimuove l'articolo; totale e conteggio si aggiornano da soli. */
    public function removeItem(int $id): void
    {
        // TODO: backend reale — per ora la lista vive solo in memoria per la durata del componente.
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item): bool => $item['id'] !== $id,
        ));
    }

    /** "Modifica" apre il pop-up condiviso caricando le copie di lavoro dell'articolo. */
    public function openEdit(int $id): void
    {
        $index = $this->findIndex($id);

        if ($index === null) {
            return;
        }

        $item = $this->items[$index];

        $this->editingId = $id;
        $this->editCheckIn = $item['dates']['checkIn'] ?? null;
        $this->editCheckOut = $item['dates']['checkOut'] ?? null;
        $this->editGuests = $item['guests'];
        $this->editDogs = $item['dogs'];
        $this->expandedField = null;

        // Il calendario parte dal mese del check-in dell'articolo.
        $start = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : new DateTimeImmutable('today');
        $this->calendarMonth = (int) $start->format('n');
        $this->calendarYear = (int) $start->format('Y');

        Flux::modal('edit-booking')->show();
    }

    /** "Annulla": chiude il pop-up scartando le copie di lavoro. */
    public function closeEdit(): void
    {
        $this->editingId = null;
        $this->expandedField = null;

        Flux::modal('edit-booking')->close();
    }

    /** "Conferma": riversa date/ospiti/animali nell'articolo e chiude. */
    public function confirmEdit(): void
    {
        $index = $this->editingId !== null ? $this->findIndex($this->editingId) : null;

        if ($index !== null) {
            if ($this->items[$index]['dates'] !== null && $this->editCheckIn !== null) {
                $this->items[$index]['dates'] = [
                    'checkIn' => $this->editCheckIn,
                    // Intervallo lasciato a metà: check-out = check-in (giorno singolo).
                    'checkOut' => $this->editCheckOut ?? $this->editCheckIn,
                ];
            }

            $this->items[$index]['guests'] = $this->editGuests;
            $this->items[$index]['dogs'] = $this->editDogs;
            // TODO: pricing backend — il prezzo resta statico anche dopo la modifica.
        }

        $this->closeEdit();
    }

    /** Apre/chiude un campo del pop-up; aprirne uno collassa gli altri. */
    public function toggleField(string $field): void
    {
        if (! in_array($field, self::FIELDS, true)) {
            return;
        }

        $this->expandedField = $this->expandedField === $field ? null : $field;
    }

    /** Freccia sinistra del calendario. */
    public function previousMonth(): void
    {
        $this->calendarMonth--;

        if ($this->calendarMonth < 1) {
            $this->calendarMonth = 12;
            $this->calendarYear--;
        }
    }

    /** Freccia destra del calendario. */
    public function nextMonth(): void
    {
        $this->calendarMonth++;

        if ($this->calendarMonth > 12) {
            $this->calendarMonth = 1;
            $this->calendarYear++;
        }
    }

    /**
     * Click su un giorno del calendario (data Y-m-d): imposta il check-in;
     * un click su un giorno successivo imposta il check-out; un click prima
     * del check-in (o a intervallo già completo) fa ripartire la selezione.
     */
    public function selectDay(string $date): void
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($day === false) {
            return;
        }

        $checkIn = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : null;

        if ($checkIn === null || $day < $checkIn || $this->editCheckOut !== null) {
            $this->editCheckIn = $day->format('d/m/Y');
            $this->editCheckOut = null;
        } else {
            $this->editCheckOut = $day->format('d/m/Y');
        }
    }

    /** Stepper "+" del pop-up Ospiti. */
    public function incrementGuest(string $key): void
    {
        if (array_key_exists($key, $this->editGuests)) {
            $this->editGuests[$key]++;
        }
    }

    /** Stepper "−" del pop-up Ospiti (adulti minimo 1, ragazzi/bambini minimo 0). */
    public function decrementGuest(string $key): void
    {
        if (array_key_exists($key, $this->editGuests)) {
            $min = $key === 'adulti' ? 1 : 0;
            $this->editGuests[$key] = max($min, $this->editGuests[$key] - 1);
        }
    }

    /** Stepper "+" del pop-up Animali. */
    public function incrementDogs(): void
    {
        $this->editDogs++;
    }

    /** Stepper "−" del pop-up Animali. */
    public function decrementDogs(): void
    {
        $this->editDogs = max(0, $this->editDogs - 1);
    }

    /** Etichetta ospiti: "N adulti" se ci sono solo adulti, altrimenti "N ospiti" (totale). */
    public function guestsLabel(array $guests): string
    {
        $extra = $guests['ragazzi'] + $guests['bambini'];

        if ($extra === 0) {
            return $guests['adulti'] === 1 ? '1 adulto' : $guests['adulti'].' adulti';
        }

        $total = $guests['adulti'] + $extra;

        return $total === 1 ? '1 ospite' : $total.' ospiti';
    }

    /** Etichetta animali: "1 cane" / "N cani". */
    public function dogsLabel(int $dogs): string
    {
        return $dogs === 1 ? '1 cane' : $dogs.' cani';
    }

    /** Indice dell'articolo nel carrello a partire dall'id (null se rimosso). */
    private function findIndex(int $id): ?int
    {
        foreach ($this->items as $index => $item) {
            if ($item['id'] === $id) {
                return $index;
            }
        }

        return null;
    }

    /** dd/mm/yyyy → DateTimeImmutable a mezzanotte. */
    private static function parseDate(string $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('!d/m/Y', $date) ?: new DateTimeImmutable('today');
    }

    /**
     * Griglia reale del mese mostrato: settimane da domenica (Dom … Sab come in XD),
     * con i giorni dei mesi adiacenti a completare le righe; 'inRange' marca i giorni
     * dentro l'intervallo selezionato (cerchio giallo nel calendario).
     */
    private function buildCalendar(): array
    {
        $first = new DateTimeImmutable(sprintf('%d-%02d-01', $this->calendarYear, $this->calendarMonth));
        $cursor = $first->modify('-'.(int) $first->format('w').' days');
        $lastOfMonth = $first->modify('last day of this month');

        $rangeStart = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : null;
        $rangeEnd = $this->editCheckOut !== null ? self::parseDate($this->editCheckOut) : $rangeStart;

        $weeks = [];

        do {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'day' => (int) $cursor->format('j'),
                    'date' => $cursor->format('Y-m-d'),
                    'inMonth' => (int) $cursor->format('n') === $this->calendarMonth
                        && (int) $cursor->format('Y') === $this->calendarYear,
                    'inRange' => $rangeStart !== null && $cursor >= $rangeStart && $cursor <= $rangeEnd,
                ];

                $cursor = $cursor->modify('+1 day');
            }

            $weeks[] = $week;
        } while ($cursor <= $lastOfMonth);

        return $weeks;
    }

    public function render()
    {
        // Totale ricalcolato live sugli articoli rimasti (Elimina lo aggiorna).
        $total = array_sum(array_column($this->items, 'price'));

        $editingItem = null;

        foreach ($this->items as $item) {
            if ($item['id'] === $this->editingId) {
                $editingItem = $item;
                break;
            }
        }

        return view('livewire.cart', [
            'total' => $total,
            'count' => count($this->items),
            'editingItem' => $editingItem,
            'calendar' => $this->expandedField === 'date' ? $this->buildCalendar() : [],
            'calendarLabel' => self::MONTHS[$this->calendarMonth].' '.$this->calendarYear,
            // Le 3 card "più amate" dello stato vuoto = preferiti 1-3 (combaciano con l'XD).
            'suggestions' => $this->items === [] ? array_values(array_filter(
                Favorites::FAVORITES,
                fn (array $fav): bool => in_array($fav['id'], [1, 2, 3], true),
            )) : [],
        ])->title('Carrello — AnimalAmo');
    }
}
