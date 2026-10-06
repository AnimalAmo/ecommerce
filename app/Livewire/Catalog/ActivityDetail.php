<?php

namespace App\Livewire\Catalog;

use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Livewire\Concerns\AddsCatalogProductToCart;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Partner\PartnerContacts;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Partner\ServiceOptionLabels;
use App\Services\Pricing\BookingPricingService;
use App\Support\Format;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ActivityDetail extends Component
{
    use AddsCatalogProductToCart;
    use HasBookingCalendar;
    use TogglesFavorites;

    /** Slug attività dalla rotta (es. "weekend-escursioni"); il nome differisce dal parametro {activity} per non collidere col binding Livewire. */
    public string $activitySlug = '';

    /** Visibilità del pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa", condiviso col dettaglio evento). */
    public bool $joinPopupOpen = false;

    /** Visibilità del pop-up "Aggiunto al carrello" (layout riusato dal dettaglio struttura: nessun pop-up XD dedicato alle attività, DA SEGNALARE). */
    public bool $cartPopupOpen = false;

    /** Campo espanso nel widget: null | 'ospiti' | 'animali' (uno alla volta, come il pop-up del carrello). */
    public ?string $expandedField = null;

    // La tab "Discussione" è stata rimossa insieme a quella del dettaglio evento: i thread
    // erano una costante PHP (mock XD) firmata da persone inventate, invisibile a qualsiasi
    // pulizia del database. Le discussioni reali arrivano con lo step contenuti/social.

    /** Campi espandibili del widget (le date sono fisse: derivano dalla riga evento). */
    public const FIELDS = ['ospiti', 'animali'];

    public function mount(string $activity): void
    {
        $model = Event::where('slug', $activity)->first();

        // Solo le attività multi-giorno; gli eventi restano su /eventi/{event}.
        abort_unless($model !== null && $model->type === ProductType::Activity, 404);

        $this->activitySlug = $activity;

        // Default del widget come da XD: 2 adulti e 1 animale (specie del primo pet dell'utente, altrimenti cane).
        $this->editGuests = ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0];
        $this->editAnimals = [self::defaultSpecies() => 1];
    }

    /** Apre/chiude un campo del widget; aprirne uno collassa l'altro. */
    public function toggleField(string $field): void
    {
        if (! in_array($field, self::FIELDS, true)) {
            return;
        }

        $this->expandedField = $this->expandedField === $field ? null : $field;
    }

    public function addToCart(): void
    {
        $activity = $this->activity();

        // CTA Partecipa (gratis o senza prezzo): nessun acquisto, il pop-up carrello non deve aprirsi.
        if ($activity->hasJoinCta()) {
            return;
        }

        // Date NON nelle options: derivano da starts_at/ends_at/duration_days del
        // purchasable. Titolare che incassa in struttura (difetto C7, 28/09/2026)
        // o capienza esaurita: toast danger, niente pop-up.
        if (! $this->addCatalogProductToCart($activity, [
            'guests' => $this->editGuests,
            'animals' => $this->editAnimals,
        ])) {
            return;
        }

        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function joinEvent(): void
    {
        // Attività a pagamento: il pill "Partecipa" esiste solo nella variante gratuita.
        if (! $this->activity()->hasJoinCta()) {
            return;
        }

        // TODO: partecipazione reale — per ora mostra solo il pop-up di conferma.
        $this->joinPopupOpen = true;
    }

    public function closeJoinPopup(): void
    {
        $this->joinPopupOpen = false;
    }

    private function activity(): Event
    {
        return Event::where('slug', $this->activitySlug)->firstOrFail();
    }

    /** Specie di default dello stepper animali: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }

    /**
     * Riga «Tipologia» della scheda: le categorie professionali tradotte, su
     * una riga sola (stesso idioma di PartnerServiceDetail, implode ', ').
     *
     * Richiesta della cliente (27/09/2026): un servizio professionale si
     * presenta per quello che fa, non per una data. Il testo libero di «Altro»
     * non entra qui: resta la sotto-riga grigia del blade, come time_note e
     * venue_note.
     */
    private static function categoryLabels(Event $activity): ?string
    {
        $labels = array_filter(ServiceOptionLabels::labels('activity_category', $activity->activity_categories));

        return $labels === [] ? null : implode(', ', $labels);
    }

    /**
     * La riga «Ritrovo» solo quando un ritrovo esiste davvero.
     *
     * EventPublisher creava il Venue col nome DELL'ATTIVITÀ quando il punto
     * d'incontro era vuoto (lo sta correggendo la lane del publisher), e le
     * righe già pubblicate portano ancora quel venue: la riga direbbe «Ritrovo:
     * Weekend di escursioni, Viareggio», cioè darebbe il nome della scheda per
     * un luogo. Il confronto è su tutte le traduzioni del titolo, perché il
     * publisher prendeva la variante italiana anche per una scheda letta in
     * inglese.
     *
     * Le attività mock del catalogo XD hanno un venue con un nome suo (Hotel
     * Miramare) e la riga resta. La zona in cui opera il partner non entra in
     * questa guardia: è una riga in più (richiesta della cliente, 27/09/2026),
     * non un'alternativa, e un professionista che ha compilato ANCHE un punto
     * d'incontro vero non deve perderlo.
     */
    private static function showsMeetingPoint(Event $activity): bool
    {
        $venueName = mb_strtolower(trim((string) $activity->venue?->name));

        if ($venueName === '') {
            return false;
        }

        $titles = array_map(
            fn ($title): string => mb_strtolower(trim((string) $title)),
            [$activity->title, ...array_values($activity->getTranslations('title'))],
        );

        return ! in_array($venueName, $titles, true);
    }

    /**
     * Posti ancora liberi: null = capienza illimitata (`max_participants`
     * nullo, lo stato di tutte le schede pubblicate prima del 27/09/2026),
     * altrimenti mai negativo. Stessa aritmetica di
     * AvailabilityService::ensureEventAvailable (capienza massima meno posti già
     * venduti), che resta l'unico posto dove i posti si contano sotto lock: qui
     * si decide solo cosa disegnare.
     */
    private static function seatsLeft(Event $activity): ?int
    {
        if ($activity->max_participants === null) {
            return null;
        }

        return max(0, $activity->max_participants - ($activity->booked_participants ?? 0));
    }

    /**
     * La riga «Posti disponibili» della scheda, sullo stampo di
     * EventDetail::remainingSeats (audit 28/09/2026, difetto C2): senza, il
     * cliente non ha modo di capire che scendendo di un ospite l'acquisto
     * passerebbe. Null quando la capienza è illimitata (nessuna riga, non
     * «illimitati») oppure già esaurita — in quel caso parla la dicitura al
     * posto della CTA, e «Posti disponibili: 0» direbbe sì e no insieme.
     */
    private static function remainingSeats(Event $activity): ?int
    {
        $left = self::seatsLeft($activity);

        return $left !== null && $left > 0 ? $left : null;
    }

    /**
     * Posti esauriti: non c'è spazio nemmeno per una persona. È la soglia che
     * toglie ENTRAMBE le CTA — anche «Partecipa», che gli ospiti non li conta —
     * e il riepilogo prezzi: non c'è più niente da comprare.
     *
     * Dal 27/09/2026 il partner può mettere un limite di posti, e un pulsante
     * che risponde soltanto con un errore è peggio di un pulsante assente.
     */
    private static function isSoldOut(Event $activity): bool
    {
        return self::seatsLeft($activity) === 0;
    }

    /**
     * Il carrello accetterebbe gli ospiti scelti negli stepper? È la condizione
     * ESATTA di AvailabilityService::ensureEventAvailable (rifiuta con
     * `booked + persons > max`), con le persone contate da
     * BookingPricingService::persons come fa lui.
     *
     * Difetto C2 (audit 28/09/2026): la CTA del carrello guardava soltanto
     * isSoldOut(), cioè la soglia di UNA persona, mentre il widget nasce con
     * due adulti. Con capienza 10 e nove posti venduti «Aggiungi al carrello»
     * veniva disegnato, e il click rispondeva solo con un toast d'errore.
     *
     * `addToCart()` NON prende guardie nuove: una chiamata wire manomessa deve
     * continuare a passare per AvailabilityService, che conta sotto lock.
     */
    private function hasSeatsForGuests(Event $activity): bool
    {
        $left = self::seatsLeft($activity);

        return $left === null || BookingPricingService::persons(['guests' => $this->editGuests]) <= $left;
    }

    /**
     * «+» degli ospiti spento anche al limite dei posti residui, non solo a
     * MAX_GUESTS: override di HasBookingCalendar::guestsAtMax, il cui clamp
     * fisso resta quello delle strutture. Lo stepper non deve portare il
     * cliente in uno stato che il carrello rifiuta — con tre posti liberi si
     * sale fino a tre ospiti e lì ci si ferma. Vale sia per il pulsante
     * disabilitato in vista sia per incrementGuest() lato server, perché il
     * trait passa da qui.
     *
     * I default di mount() restano due adulti anche con un solo posto libero:
     * abbassarli di nascosto cambierebbe la richiesta del cliente senza
     * dirglielo. In quel caso il «+» è già spento, la CTA lascia il posto alla
     * dicitura con i posti rimasti, e il «−» porta a una richiesta che il
     * carrello accetta.
     */
    public function guestsAtMax(): bool
    {
        return $this->guestsAtMaxFor($this->activity());
    }

    /** Il clamp di guestsAtMax() sulla riga già letta: render() non rilegge l'attività. */
    private function guestsAtMaxFor(Event $activity): bool
    {
        $limit = min(self::MAX_GUESTS, self::seatsLeft($activity) ?? self::MAX_GUESTS);

        return array_sum($this->editGuests) >= $limit;
    }

    /**
     * Date del widget (NON editabili) derivate dalla riga evento — stessa
     * derivazione della card carrello (CartItemData::dates): fine = ends_at
     * reale, altrimenti durata (3 giorni = start + 2).
     *
     * @return array{checkIn: ?string, checkOut: ?string} dd/mm/YYYY
     */
    private static function widgetDates(Event $activity): array
    {
        if ($activity->starts_at === null) {
            return ['checkIn' => null, 'checkOut' => null];
        }

        return [
            'checkIn' => Format::dateShort($activity->starts_at),
            'checkOut' => match (true) {
                $activity->ends_at !== null => Format::dateShort($activity->ends_at),
                $activity->duration_days !== null && $activity->duration_days > 1 => Format::dateShort($activity->starts_at->clone()->addDays($activity->duration_days - 1)),
                default => null,
            },
        ];
    }

    public function render()
    {
        $activity = $this->activity();

        // Durata: null nel dato = weekend XD di 3 giorni.
        $days = $activity->duration_days ?? 3;

        // Persone reali dagli stepper e totale quotato server-side (a persona; gli animali non sono prezzati).
        $persons = array_sum($this->editGuests);
        $quoteCents = $activity->hasJoinCta()
            ? null
            : app(BookingPricingService::class)->quote($activity, ['guests' => $this->editGuests, 'animals' => $this->editAnimals]);
        $isSoldOut = self::isSoldOut($activity);

        // Una lettura sola del profilo per gli orari e per le card: PartnerContacts
        // passa da PartnerPaymentModeService, `scoped`, e il `paysOnSite` qui sotto
        // riusa lo stesso profilo. Nel componente e non nel blade: in una vista una
        // relazione diventa una N+1 il giorno che la riga finisce dentro un ciclo.
        $contacts = app(PartnerContacts::class)->forPurchasable($activity);

        return view('livewire.catalog.activity-detail', [
            'activity' => $activity,
            // «Vedere tutte le foto»: vuoto con una foto sola, e allora niente pulsante né modale.
            'galleryPhotos' => $activity->galleryPhotos(),
            // Le attività sono righe Event: alias morph 'event'.
            'isFav' => $this->isFavorite('event', $activity->id),
            // L'XD non definisce un design per le attività gratuite: allineato al linguaggio
            // della variante evento gratuito ("Gratis" corsivo + pill Partecipa, niente riepilogo prezzi).
            'isFree' => $activity->is_free,
            'canJoin' => $activity->hasJoinCta(),
            // Testo XD "Durata di 3 giorni, due notti"; per le altre durate la forma numerica.
            'durationLabel' => $days === 3
                ? __('format.duration_label_weekend')
                : __('format.duration_label', ['days' => $days, 'nights' => $days - 1]),
            'priceHeadline' => $activity->price_cents !== null
                ? __('format.per_person', ['price' => Format::money($activity->price_cents)])
                : __('format.from_price', ['price' => Format::money(0)]),
            // Riepilogo prezzi reale dagli stepper (solo attività acquistabili: !hasJoinCta ⇒ price_cents non null).
            'priceForGuests' => $quoteCents !== null
                ? __('format.for_people', ['price' => Format::money($activity->price_cents), 'count' => $persons])
                : null,
            'totalPrice' => $quoteCents !== null ? Format::money($quoteCents) : null,
            // Date fisse del widget derivate dalla riga evento (starts_at/ends_at/duration_days).
            'dates' => self::widgetDates($activity),
            'guestsLabel' => Format::guests($this->editGuests),
            'animalsLabel' => Format::animals($this->editAnimals),
            'guestsAtMax' => $this->guestsAtMaxFor($activity),
            'animalsAtMax' => $this->animalsAtMax(),
            // array_filter: amenityRows torna solo le voci offerte, quindi una
            // colonna può restare vuota — e la griglia a due colonne del blade
            // lascerebbe metà sezione bianca. La si toglie qui, non nel template.
            'includedColumns' => array_values(array_filter([
                $activity->amenityRows('hotel'),
                $activity->amenityRows('animal'),
            ])),
            'faqs' => $activity->faqs,
            // Le quattro righe nate dalle risposte della cliente del 27/09/2026:
            // sulla scheda di un'attività o di un servizio professionale, al posto
            // di «Data inizio / Data fine», la tipologia, la zona in cui opera, gli
            // orari del titolare e se la prenotazione serve. Ognuna è nulla quando
            // il partner non l'ha compilata, e il blade salta la riga: mai
            // un'etichetta senza valore.
            'categoryLabels' => self::categoryLabels($activity),
            // Orari del titolare (richiesta della cliente, 27/09/2026: stanno sul
            // partner, non sul servizio, perché un professionista ha un orario
            // solo per tutte le sue schede).
            'openingHours' => $contacts['opening_hours'] ?? null,
            'bookingRequirement' => ServiceOptionLabels::label('booking_requirement', $activity->booking_requirement),
            'showMeetingPoint' => self::showsMeetingPoint($activity),
            'isSoldOut' => $isSoldOut,
            // Difetto C2 (audit 28/09/2026): posti ci sono, ma non per gli ospiti
            // scelti. Solo per le attività acquistabili — «Partecipa» non passa
            // dal carrello e non conta gli ospiti — e mai insieme a isSoldOut,
            // che ha già la sua dicitura. Il blade toglie il pulsante del
            // carrello e al suo posto dice quanti posti restano.
            'notEnoughSeats' => ! $activity->hasJoinCta() && ! $isSoldOut && ! $this->hasSeatsForGuests($activity),
            'remainingSeats' => self::remainingSeats($activity),
            // Solo le attività acquistabili: quelle gratuite restano "Partecipa" e
            // non passano mai dal carrello, quindi non serve nemmeno la query.
            'paysOnSite' => ! $activity->hasJoinCta()
                && app(PartnerPaymentModeService::class)->forPurchasable($activity) === OrderPaymentMode::OnSite,
            'contacts' => $contacts,
        ])->title('AnimalAmo — '.$activity->title);
    }
}
