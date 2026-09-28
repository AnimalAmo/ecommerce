<?php

namespace App\Livewire\Commerce;

use App\Data\Cart\CartItemData;
use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Structure\Structure;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartNotice;
use App\Services\Content\FaqService;
use App\Services\FavoriteService;
use App\Services\Partner\PartnerPaymentModeService;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Cart extends Component
{
    use HasBookingCalendar;

    // I cuori delle card «più amate» dello stato vuoto sono quelli del catalogo (difetto C6).
    use TogglesFavorites;

    /** Flag ?regalo=1 (deep-link come ?tab della community): mostra SOLO le righe regalo (flussi separati, mai vista mista). */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Dedica e messaggio della riga regalo, per chiave riga (persistiti da goToCheckout via updateGift). */
    public array $giftDedication = [];

    public array $giftMessage = [];

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

    /**
     * Frasi dell'avviso «il tuo carrello è cambiato»: righe tolte senza che le
     * togliesse il cliente — prodotto ritirato, sospeso o cancellato, righe
     * ospite scartate all'accesso.
     *
     * Difetto C9 (audit 28/09/2026): prima quelle righe sparivano in silenzio e
     * il totale scendeva senza una parola. CartNotice::pull() svuota l'avviso
     * mentre lo consegna, quindi si vede una volta; qui resta per tutta la
     * visita (modificare o eliminare un'altra riga non lo fa sparire) finché
     * il cliente non lo chiude. Locked: lo scrive solo il server.
     *
     * @var list<string>
     */
    #[Locked]
    public array $removedNotice = [];

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
        // Questi due campi sono l'unico punto in cui un testo scritto da chi
        // compra finisce dentro una mail spedita da noi a un indirizzo che non
        // ci ha chiesto niente: vanno tenuti corti, o un messaggio enorme
        // gonfia il messaggio in uscita senza che nessuno se ne accorga.
        $this->validate([
            'giftDedication.*' => ['nullable', 'string', 'max:200'],
            'giftMessage.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($this->cart()->items(true) as $item) {
            $this->cart()->updateGift($item->key, [
                'dedication' => $this->giftDedication[$item->key] ?? '',
                'message' => $this->giftMessage[$item->key] ?? '',
            ]);
        }

        return $this->redirectRoute('checkout', ['regalo' => 1]);
    }

    /**
     * Cuore sulle card suggerite dello stato vuoto ($key = 'alias-id' della
     * card FavoriteService::topFavorited): preferito vero, lo stesso del cuore
     * del catalogo e delle card «più amate» di /preferiti (TogglesFavorites →
     * FavoriteService::toggle). Da ospite apre il login, come ovunque.
     *
     * Difetto C6 (audit 28/09/2026): prima infilava e toglieva la chiave da un
     * array del componente — il cuore diventava giallo, nessun preferito
     * nasceva e ricaricando la pagina tornava bianco. Anche lo stato iniziale
     * mentiva: partiva bianco pure sui prodotti già tra i preferiti. Ora il
     * cuore si accende dai preferiti reali (isFavorite nella vista).
     */
    public function toggleSuggestionFavorite(string $key): void
    {
        [$type, $id] = self::suggestionTarget($key);

        $this->toggleFavorite($type, $id);
    }

    /**
     * Borsa sulle card suggerite dello stato vuoto: aggiunge davvero al
     * carrello con le opzioni di default della famiglia, con le stesse regole
     * della borsa di /preferiti (FavoriteService::addProductToCart) e sullo
     * stampo di Favorites::toggleCart(): toast danger sulle violazioni,
     * `cart-updated` per il badge, toast di conferma.
     *
     * Difetto C6 (audit 28/09/2026): prima era solo colore — il bottone
     * diventava giallo e l'aria-label diceva «Rimuovi dal carrello» su un
     * carrello vuoto, il contatore non si muoveva e ricaricando spariva tutto.
     *
     * Niente rimozione, come su /preferiti: le card suggerite esistono solo a
     * carrello vuoto, e dopo l'aggiunta render() rilegge le righe — lo stato
     * vuoto lascia il posto alla lista con la riga nuova e il totale, dove si
     * modifica e si elimina. L'ospite può aggiungere: il suo carrello vive in
     * sessione (CartManager), quindi non serve mandarlo al login.
     */
    public function toggleSuggestionCart(string $key): void
    {
        [$type, $id] = self::suggestionTarget($key);

        try {
            $added = app(FavoriteService::class)->addProductToCart(auth()->user(), $type, $id);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        // Prodotto non acquistabile (evento gratuito): nessuna riga, nessun feedback.
        if (! $added) {
            return;
        }

        $this->dispatch('cart-updated');
        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** La X dell'avviso «il tuo carrello è cambiato»: il cliente l'ha letto. */
    public function dismissRemovedNotice(): void
    {
        $this->removedNotice = [];
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
        $cartItems = $this->cart()->items($this->gift);

        // Dopo la lettura delle righe, non prima: è la lettura stessa che toglie
        // le righe fantasma e ne scrive l'avviso (difetto C9), e va detto adesso.
        $removed = app(CartNotice::class)->pull();

        if ($removed !== []) {
            $this->removedNotice = array_values(array_unique([...$this->removedNotice, ...$removed]));

            // Il badge dell'header conta le righe per conto suo: che non resti indietro.
            $this->dispatch('cart-updated');
        }

        // Un carrello = un partner (CartManager::guardSinglePartner): basta la
        // prima riga già caricata, senza rileggere il carrello. Nessuna riga
        // → null → Online (e la vista non stampa il riepilogo).
        $paysOnSite = app(PartnerPaymentModeService::class)
            ->forOwner($cartItems->first()?->partnerUserId) === OrderPaymentMode::OnSite;

        // Kill-switch spento (richiesta della cliente, 27/09/2026): il carrello
        // resta com'è — righe, totale, Modifica ed Elimina intatti — ma le CTA
        // verso il checkout diventano la spiegazione. Mandare il cliente a
        // sbattere contro il blocco due schermate dopo sarebbe peggio.
        $onSiteBlocked = $paysOnSite && ! (bool) config('commerce.on_site_booking');

        $items = $cartItems
            ->map(fn (CartItemData $item): array => $this->presentItem($item))
            ->values()
            ->all();

        $editingItem = $this->editingKey !== null
            ? collect($items)->first(fn (array $item): bool => (string) $item['id'] === (string) $this->editingKey)
            : null;

        // Le 3 card "più amate" reali dello stato vuoto (query sui preferiti).
        $suggestions = $items === [] ? app(FavoriteService::class)->topFavorited() : [];

        // Nel flusso regalo la borsa delle card suggerite sparisce: aggiungerebbe
        // una riga normale, che la vista regalo (mai mista) non mostra, e il
        // bottone sembrerebbe morto. Il cuore resta: il preferito non dipende dal flusso.
        if ($this->gift) {
            $suggestions = array_map(
                fn (array $suggestion): array => [...$suggestion, 'can_add_to_cart' => false],
                $suggestions,
            );
        }

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
            'suggestions' => $suggestions,
            'paysOnSite' => $paysOnSite,
            // Modalità non più disponibile: al posto delle CTA la spiegazione.
            'onSiteBlocked' => $onSiteBlocked,
            // I contatti del partner stanno sulla scheda del prodotto (il carrello
            // non li duplica): serve il link per arrivarci.
            'onSiteProductUrl' => $onSiteBlocked ? self::productUrl($cartItems->first()) : null,
        ])->title(__('cart.ui.page_title'));
    }

    /**
     * Scheda pubblica del prodotto di una riga: col pagamento diretto al partner
     * spento è l'unico posto dove il cliente trova i recapiti per prenotare.
     *
     * La mappatura purchasable → rotta pubblica esiste già in FaqService (con la
     * trappola della struttura senza regione, che manderebbe route() in errore):
     * riusarla è meglio che riscriverla qui. Null = niente link (cofanetti e
     * strutture senza regione), il pannello resta la sola spiegazione.
     */
    private static function productUrl(?CartItemData $item): ?string
    {
        if ($item === null) {
            return null;
        }

        $class = Relation::getMorphedModel($item->type);
        $product = $class !== null ? $class::find($item->purchasableId) : null;

        return $product instanceof Model ? app(FaqService::class)->productUrl($product) : null;
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
     * Chiave di una card suggerita ('alias-id', vedi FavoriteService::topFavorited)
     * → [alias morph, id prodotto]. Arriva dal payload del client: una chiave
     * malformata è un 400; alias fuori whitelist e prodotto inesistente li
     * respingono i service (400/404), come per il cuore del catalogo.
     *
     * @return array{0: string, 1: int}
     */
    private static function suggestionTarget(string $key): array
    {
        abort_unless(preg_match('/^([a-z_]+)-(\d+)$/', $key, $matches) === 1, 400);

        return [$matches[1], (int) $matches[2]];
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
