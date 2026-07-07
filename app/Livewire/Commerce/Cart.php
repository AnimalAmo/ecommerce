<?php

namespace App\Livewire\Commerce;

use App\Data\Cart\CartItemData;
use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\Structure\Structure;
use App\Services\Cart\CartManager;
use App\Support\Format;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Url;
use Livewire\Component;

class Cart extends Component
{
    use HasBookingCalendar;

    /** Flag ?regalo=1 (deep-link come ?tab della community): mostra SOLO le righe regalo (flussi separati, mai vista mista). */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Dedica e messaggio della riga regalo, per chiave riga (persistiti da goToCheckout via updateGift). */
    public array $giftDedication = [];

    public array $giftMessage = [];

    /** Chiavi ('alias-id') delle card suggerite (stato vuoto) marcate preferite / aggiunte al carrello (solo visivo). */
    public array $suggestFavorites = [];

    public array $suggestInCart = [];

    /** Chiave della riga in modifica nel pop-up: id cart_items (auth) o hash md5 (sessione); null = pop-up chiuso. */
    public int|string|null $editingKey = null;

    /** Famiglia della riga in modifica (structure/service/activity/smartbox): decide gli accordion del pop-up. */
    public ?string $editingFamily = null;

    /** Copie di lavoro degli orari del servizio ('10:00'), solo famiglia service. */
    public ?string $editTimeFrom = null;

    public ?string $editTimeTo = null;

    /** Campo espanso nel pop-up: null | 'date' | 'ospiti' | 'animali' | 'orari' (uno alla volta). */
    public ?string $expandedField = null;

    /** Campi espandibili ammessi nel pop-up ('date' copre anche il giorno singolo del service). */
    public const FIELDS = ['date', 'ospiti', 'animali', 'orari'];

    public function mount(): void
    {
        if (! $this->gift) {
            return;
        }

        // Idrata dedica/messaggio già persistiti sulle righe regalo (chiave = riga).
        foreach ($this->cart()->items(true) as $item) {
            $this->giftDedication[$item->key] = $item->options['gift']['dedication'] ?? '';
            $this->giftMessage[$item->key] = $item->options['gift']['message'] ?? '';
        }
    }

    /** CTA "Vai al checkout" in modalità regalo: persiste dedica/messaggio sulle righe e apre il checkout regalo. */
    public function goToCheckout()
    {
        foreach ($this->cart()->items(true) as $item) {
            $this->cart()->updateGift($item->key, [
                'dedication' => $this->giftDedication[$item->key] ?? '',
                'message' => $this->giftMessage[$item->key] ?? '',
            ]);
        }

        return $this->redirectRoute('checkout', ['regalo' => 1]);
    }

    /** Cuore sulle card suggerite dello stato vuoto: parte bianco e diventa giallo (toggle, solo visivo). */
    public function toggleSuggestionFavorite(string $key): void
    {
        if (in_array($key, $this->suggestFavorites, true)) {
            $this->suggestFavorites = array_values(array_diff($this->suggestFavorites, [$key]));
        } else {
            $this->suggestFavorites[] = $key;
        }
    }

    /** Borsa sulle card suggerite dello stato vuoto: aggiunge/toglie dal carrello (solo visivo). */
    public function toggleSuggestionCart(string $key): void
    {
        if (in_array($key, $this->suggestInCart, true)) {
            $this->suggestInCart = array_values(array_diff($this->suggestInCart, [$key]));
        } else {
            $this->suggestInCart[] = $key;
        }
    }

    /** Il bottone "Elimina" rimuove la riga dal carrello; totale e conteggio si aggiornano da soli. */
    public function removeItem(int|string $key): void
    {
        $this->cart()->removeItem($key);
        $this->dispatch('cart-updated');
    }

    /** "Modifica" apre il pop-up condiviso caricando le copie di lavoro dalle options della riga. */
    public function openEdit(int|string $key): void
    {
        $item = $this->findItem($key);

        if ($item === null) {
            return;
        }

        $family = self::familyOf($item);

        // Gli eventi hanno data fissa e 1 partecipante: nessuna Modifica.
        if ($family === 'event') {
            return;
        }

        $options = $item->options;

        $this->editingKey = $item->key;
        $this->editingFamily = $family;
        $this->expandedField = null;
        $this->calendarSingleDay = $family === 'service';

        $this->editCheckIn = null;
        $this->editCheckOut = null;
        $this->editTimeFrom = null;
        $this->editTimeTo = null;
        $this->editGuests = $options['guests'] ?? ['adulti' => 1, 'ragazzi' => 0, 'bambini' => 0];
        $this->editAnimals = $options['animals'] ?? ['cane' => 0];

        if ($family === 'structure') {
            $this->editCheckIn = self::displayDate($options['check_in']);
            $this->editCheckOut = self::displayDate($options['check_out']);
        }

        if ($family === 'service') {
            $this->editCheckIn = self::displayDate($options['day']);
            $this->editTimeFrom = $options['time_from'];
            $this->editTimeTo = $options['time_to'];
        }

        // Il calendario parte dal mese della data della riga (oggi se assente).
        $start = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : new DateTimeImmutable('today');
        $this->pointCalendarAt($start);

        Flux::modal('edit-booking')->show();
    }

    /** "Annulla": chiude il pop-up scartando le copie di lavoro. */
    public function closeEdit(): void
    {
        $this->editingKey = null;
        $this->editingFamily = null;
        $this->expandedField = null;

        Flux::modal('edit-booking')->close();
    }

    /**
     * "Conferma": fonde le copie di lavoro nelle options della riga (il manager
     * rivalida disponibilità e riprezza); violazione = toast danger e pop-up aperto.
     */
    public function confirmEdit(): void
    {
        if ($this->editingKey === null) {
            return;
        }

        try {
            $this->cart()->updateItem($this->editingKey, $this->editOptions());
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
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

    public function render()
    {
        $items = $this->cart()->items($this->gift)
            ->map(fn (CartItemData $item): array => $this->presentItem($item))
            ->values()
            ->all();

        $editingItem = $this->editingKey !== null
            ? collect($items)->first(fn (array $item): bool => (string) $item['id'] === (string) $this->editingKey)
            : null;

        return view('livewire.commerce.cart', [
            'items' => $items,
            'total' => $this->cart()->total($this->gift),
            'count' => count($items),
            'editingItem' => $editingItem,
            'calendar' => $this->expandedField === 'date' ? $this->buildCalendar() : [],
            'calendarLabel' => $this->calendarLabel(),
            'guestsAtMax' => $this->guestsAtMax(),
            'animalsAtMax' => $this->animalsAtMax(),
            'bookingHours' => self::bookingHours(),
            // Le 3 card "più amate" reali dello stato vuoto (query sui preferiti).
            'suggestions' => $items === [] ? $this->suggestions() : [],
        ])->title('Carrello — AnimalAmo');
    }

    /** Struttura del calendario del pop-up: il purchasable della riga in modifica (solo structure/service). */
    protected function calendarStructure(): ?Structure
    {
        if ($this->editingKey === null || ! in_array($this->editingFamily, ['structure', 'service'], true)) {
            return null;
        }

        $item = $this->findItem($this->editingKey);

        return $item !== null ? Structure::find($item->purchasableId) : null;
    }

    /** Facciata carrello (singleton: storage sessione da guest, db da autenticato). */
    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    /** Riga del carrello (nel flusso corrente) a partire dalla chiave (null se rimossa). */
    private function findItem(int|string $key): ?CartItemData
    {
        return $this->cart()->items($this->gift)->first(
            fn (CartItemData $item): bool => (string) $item->key === (string) $key,
        );
    }

    /**
     * DTO riga → array della card blade: id = chiave riga, prezzo in cents
     * (display via Format::money), animali {specie: count} (label via
     * Format::animals), chip = ProductType REALE del prodotto.
     */
    private function presentItem(CartItemData $item): array
    {
        $family = self::familyOf($item);

        return [
            'id' => $item->key,
            'family' => $family,
            'type' => $item->productType,
            'title' => $item->title,
            'location' => $item->location,
            'photoUrl' => $item->photoUrl,
            'dates' => $item->dates,
            'serviceSlot' => $item->serviceSlot,
            // Evento: niente ospiti nelle options, la riga mostra i partecipanti (sempre 1 dalla pagina).
            'guests' => match ($family) {
                'structure', 'activity' => $item->options['guests'] ?? null,
                'event' => ['adulti' => (int) ($item->options['participants'] ?? 1), 'ragazzi' => 0, 'bambini' => 0],
                default => null,
            },
            'animals' => $item->options['animals'] ?? null,
            'price' => $item->priceCents,
            'gift' => $item->isGift,
            'giftValidity' => $item->giftValidity,
        ];
    }

    /**
     * Famiglia della riga da alias morph + ProductType (stessa logica di
     * BookingPricingService::family, senza ricaricare il modello).
     */
    private static function familyOf(CartItemData $item): string
    {
        return match (true) {
            $item->type === 'smartbox_package' => 'smartbox',
            $item->type === 'structure' => $item->productType === ProductType::Service->value ? 'service' : 'structure',
            default => $item->productType === ProductType::Activity->value ? 'activity' : 'event',
        };
    }

    /** Options del pop-up per famiglia (merge lato manager: le chiavi non toccate restano). */
    private function editOptions(): array
    {
        $options = match ($this->editingFamily) {
            'structure' => [
                'check_in' => self::isoDate($this->editCheckIn),
                // Intervallo lasciato a metà: check-out = check-in (la validazione server segnala il range non valido).
                'check_out' => self::isoDate($this->editCheckOut ?? $this->editCheckIn),
                'guests' => $this->editGuests,
                'animals' => $this->editAnimals,
            ],
            'service' => [
                'day' => self::isoDate($this->editCheckIn),
                'time_from' => in_array($this->editTimeFrom, self::bookingHours(), true) ? $this->editTimeFrom : null,
                'time_to' => in_array($this->editTimeTo, self::bookingHours(), true) ? $this->editTimeTo : null,
                'animals' => $this->editAnimals,
            ],
            'activity' => [
                'guests' => $this->editGuests,
                'animals' => $this->editAnimals,
            ],
            default => ['animals' => $this->editAnimals],
        };

        // Valori assenti/non validi omessi: il merge del manager conserva quelli correnti.
        return array_filter($options, fn (mixed $value): bool => $value !== null);
    }

    /**
     * Le 3 attività "più amate" reali per lo stato vuoto: top prodotti per
     * numero di preferiti (tie-break id crescente), presentati nel contratto
     * della card condivisa (come FavoriteService::present, ma con prezzo
     * "A partire da" = price_from_cents).
     *
     * @return list<array<string, mixed>>
     */
    private function suggestions(): array
    {
        return Favorite::query()
            ->select(['favoritable_type', 'favoritable_id'])
            ->selectRaw('count(*) as favorites_count')
            ->groupBy('favoritable_type', 'favoritable_id')
            ->orderByDesc('favorites_count')
            ->orderBy('favoritable_id')
            ->limit(3)
            ->get()
            ->map(function (Favorite $row): ?array {
                $product = Relation::getMorphedModel($row->favoritable_type)::find($row->favoritable_id);

                // Prodotti nel frattempo rimossi dal catalogo: card saltata.
                return $product !== null ? $this->presentSuggestion($row->favoritable_type, $product) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /** Presenta il prodotto suggerito nella card condivisa, per famiglia (id = 'alias-id', unico tra i morph). */
    private function presentSuggestion(string $alias, Model $product): array
    {
        $card = match (true) {
            $product instanceof Event => [
                'title' => $product->title,
                'location' => $product->location,
                ...($product->type === ProductType::Activity && $product->duration_days
                    ? ['metaType' => 'durata', 'metaText' => mb_strtoupper(__('format.duration_days', ['days' => $product->duration_days]))]
                    : ['metaType' => 'data', 'metaText' => $product->starts_at ? Format::eventTime($product->starts_at) : '']),
                // Gli eventi non hanno price_from: prezzo pieno (0 per i gratuiti, '0,00 €' fedele al mock).
                'price' => Format::money($product->price_cents ?? 0),
            ],
            $product instanceof Structure => [
                'title' => $product->name,
                'location' => $product->location,
                'metaType' => 'rating',
                'metaText' => Format::rating($product->rating),
                'price' => Format::money($product->price_from_cents),
            ],
            // SmartboxPackage: la riga pin mostra l'audience, la riga durata la validità.
            default => [
                'title' => $product->title,
                'location' => $product->audience,
                'metaType' => 'durata',
                'metaText' => mb_strtoupper(__('format.valid_for', ['validity' => Format::validity($product->validity_months)])),
                'price' => Format::money($product->price_from_cents),
            ],
        };

        return $card + [
            'id' => $alias.'-'.$product->getKey(),
            'type' => $product->type->value,
            'photo' => $product->img.'.jpg',
        ];
    }

    /** 'Y-m-d' (options) → 'dd/mm/yyyy' (display pop-up). */
    private static function displayDate(string $date): string
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return ($day ?: new DateTimeImmutable('today'))->format('d/m/Y');
    }

    /** 'dd/mm/yyyy' (display) → 'Y-m-d' (options); null resta null. */
    private static function isoDate(?string $date): ?string
    {
        return $date !== null ? self::parseDate($date)->format('Y-m-d') : null;
    }
}
