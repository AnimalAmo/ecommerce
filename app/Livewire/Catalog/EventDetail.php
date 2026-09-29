<?php

namespace App\Livewire\Catalog;

use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Livewire\Concerns\AddsCatalogProductToCart;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Partner\PartnerContacts;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Partner\ServiceOptionLabels;
use App\Support\Format;
use Livewire\Component;

class EventDetail extends Component
{
    use AddsCatalogProductToCart;
    use TogglesFavorites;

    /** Slug evento dalla rotta (es. "brunch-pet-friendly"); il nome differisce dal parametro {event} per non collidere col binding Livewire. */
    public string $eventSlug = '';

    /** Visibilità del pop-up "Aggiunto al carrello" (XD: "Pop-up evento acquista"). */
    public bool $cartPopupOpen = false;

    /** Visibilità del pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa"). */
    public bool $joinPopupOpen = false;

    // La tab "Discussione" è stata rimossa: i thread erano una costante PHP (mock XD)
    // firmata da persone inventate, quindi nessuna pulizia del database poteva toglierli
    // e sulla scheda di un partner sarebbero apparse risposte attribuite a lui.
    // Le discussioni reali arrivano con lo step contenuti/social: fino ad allora la
    // scheda ha una sola sezione (Informazioni) e le FAQ restano nella colonna destra.

    public function mount(string $event): void
    {
        $model = Event::where('slug', $event)->first();

        abort_unless($model !== null, 404);

        // Le attività multi-giorno hanno una scheda dedicata (stesso pattern struttura → servizio).
        if ($model->type === ProductType::Activity) {
            $this->redirectRoute('eventi.activity', ['activity' => $event]);

            return;
        }

        $this->eventSlug = $event;
    }

    public function addToCart(): void
    {
        $event = $this->event();

        // CTA Partecipa (gratis o senza prezzo): nessun acquisto, il pop-up carrello non deve aprirsi.
        if ($event->hasJoinCta()) {
            return;
        }

        // Pagina senza contatore partecipanti: sempre 1 persona per aggiunta
        // (decisione ratificata). Titolare che incassa in struttura (difetto C7,
        // 28/09/2026) o capienza esaurita: toast danger, niente pop-up.
        if (! $this->addCatalogProductToCart($event, ['participants' => 1])) {
            return;
        }

        $this->cartPopupOpen = true;
    }

    public function joinEvent(): void
    {
        // Evento a pagamento: il pill "Partecipa" non esiste, il pop-up partecipa non deve aprirsi.
        if (! $this->event()->hasJoinCta()) {
            return;
        }

        // TODO: partecipazione reale — per ora mostra solo il pop-up di conferma.
        $this->joinPopupOpen = true;
    }

    public function closeJoinPopup(): void
    {
        $this->joinPopupOpen = false;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    private function event(): Event
    {
        return Event::where('slug', $this->eventSlug)->firstOrFail();
    }

    /**
     * Riga «Tipologia» della scheda: le tipologie di evento tradotte su una
     * riga sola (stesso idioma di PartnerServiceDetail, implode ', ').
     *
     * Gruppo `event_category` e non `activity_category`: sono le due liste
     * gemelle dello stesso step del wizard (risposta della cliente,
     * 27/09/2026), e gli slug non si sovrappongono — un evento pubblicato da
     * chi prima faceva il professionista non deve pescare nella lista
     * sbagliata. Il testo libero di «Altro» resta la sotto-riga grigia del
     * blade, come time_note e venue_note.
     */
    private static function categoryLabels(Event $event): ?string
    {
        $labels = array_filter(ServiceOptionLabels::labels('event_category', $event->event_categories));

        return $labels === [] ? null : implode(', ', $labels);
    }

    /**
     * Posti che restano, o null quando la capienza è illimitata
     * (`max_participants` nullo) oppure già esaurita — in quel caso la riga
     * lascia il posto alla dicitura "posti esauriti", che sta dove c'era la
     * CTA: «Posti disponibili: 0» sarebbe una riga che dice di sì e di no.
     *
     * Stessa aritmetica di AvailabilityService::ensureEventAvailable, che è
     * l'unico posto dove i posti si contano sotto lock: qui si decide solo cosa
     * disegnare.
     */
    private static function remainingSeats(Event $event): ?int
    {
        if ($event->max_participants === null) {
            return null;
        }

        $left = $event->max_participants - ($event->booked_participants ?? 0);

        return $left > 0 ? $left : null;
    }

    /**
     * Posti esauriti: non c'è spazio nemmeno per un partecipante (la scheda
     * evento aggiunge sempre una persona per volta, decisione ratificata).
     *
     * Dal 27/09/2026 il partner può mettere un limite di posti, quindi un
     * evento può riempirsi: la CTA sparisce invece di restare lì a fallire con
     * un toast. `addToCart()` NON prende una guardia sui posti — la validazione vera
     * resta del carrello, che è l'unico a contare sotto lock, e una chiamata
     * wire manomessa deve continuare a passare da lì.
     */
    private static function isSoldOut(Event $event): bool
    {
        return $event->max_participants !== null
            && ($event->booked_participants ?? 0) >= $event->max_participants;
    }

    public function render()
    {
        $event = $this->event();

        return view('livewire.catalog.event-detail', [
            'event' => $event,
            // «Vedere tutte le foto»: copertina + galleria pubblicata; il pulsante compare da due foto in su.
            'galleryPhotos' => $event->galleryImageUrls(),
            'isFav' => $this->isFavorite('event', $event->id),
            'isFree' => $event->is_free,
            'canJoin' => $event->hasJoinCta(),
            // Prezzo nel pop-up: solo eventi acquistabili (prezzo reale, mai il fallback mock);
            // i gratuiti/senza prezzo hanno la CTA Partecipa e il pop-up carrello non esiste.
            'popupPrice' => $event->price_cents !== null ? Format::money($event->price_cents) : null,
            // Una colonna vuota va tolta qui: la griglia a due colonne del blade
            // lascerebbe metà sezione bianca (vedi ActivityDetail).
            'includedColumns' => array_values(array_filter([
                $event->amenityRows('hotel'),
                $event->amenityRows('animal'),
            ])),
            'faqs' => $event->faqs,
            // Le righe nate dalle risposte della cliente del 27/09/2026: che tipo
            // di evento è, se è singolo o ricorrente, se la prenotazione serve e
            // quanti posti restano. Ognuna è nulla quando il partner non l'ha
            // compilata, e il blade salta la riga: mai un'etichetta senza valore.
            'categoryLabels' => self::categoryLabels($event),
            'recurrenceLabel' => ServiceOptionLabels::label('event_recurrence', $event->recurrence),
            'bookingRequirement' => ServiceOptionLabels::label('booking_requirement', $event->booking_requirement),
            'remainingSeats' => self::remainingSeats($event),
            'isSoldOut' => self::isSoldOut($event),
            // Solo gli eventi acquistabili: i gratuiti hanno la CTA Partecipa.
            'paysOnSite' => ! $event->hasJoinCta()
                && app(PartnerPaymentModeService::class)->forPurchasable($event) === OrderPaymentMode::OnSite,
            'contacts' => app(PartnerContacts::class)->forPurchasable($event),
        ])->title('AnimalAmo — '.$event->title);
    }
}
