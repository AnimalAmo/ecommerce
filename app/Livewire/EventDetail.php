<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use App\Support\Format;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;

class EventDetail extends Component
{
    use TogglesFavorites;

    /** Slug evento dalla rotta (es. "brunch-pet-friendly"); il nome differisce dal parametro {event} per non collidere col binding Livewire. */
    public string $eventSlug = '';

    /** Tab attiva ("informazioni" | "discussione"); deep-linkabile via ?tab=discussione. */
    #[Url(except: 'informazioni')]
    public string $tab = 'informazioni';

    /** Visibilità del pop-up "Aggiunto al carrello" (XD: "Pop-up evento acquista"). */
    public bool $cartPopupOpen = false;

    /** Visibilità del pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa"). */
    public bool $joinPopupOpen = false;

    public const TABS = ['informazioni', 'discussione'];

    /**
     * Tab "Discussione" — thread di esempio come da XD; le discussioni reali
     * arrivano con lo step 5 (contenuti/social).
     */
    public const THREADS = [
        [
            'messages' => [
                ['author' => 'Andrea', 'body' => 'Buongiorno a tutti, vorrei sapere se è disponibile un luogo per lasciare custoditi tutti gli accessori dei propri animali. Ho un gatto, quindi vorrei riporre il trasportino. Grazie per la risposta'],
                ['author' => 'Giulia', 'body' => 'Buongiorno Andrea, sono l’organizzatrice, ti confermo che è presente una stanza per riporre tutto quello che vuoi.'],
            ],
        ],
        [
            'messages' => [
                ['author' => 'Sofia', 'body' => 'Ciao, è possibile avere del cibo vegetariano?'],
                ['author' => 'Giulia', 'body' => 'Ciao Sofia, certo il Brunch comprende diversi menu, per venire in contro alle diverse esigenze.'],
                ['author' => 'Sofia', 'body' => 'Grazie mille :)'],
            ],
        ],
    ];

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

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'informazioni';
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
        }
    }

    public function addToCart(): void
    {
        $event = $this->event();

        // CTA Partecipa (gratis o senza prezzo): nessun acquisto, il pop-up carrello non deve aprirsi.
        if ($event->hasJoinCta()) {
            return;
        }

        try {
            // Pagina senza contatore partecipanti: sempre 1 persona per aggiunta (decisione ratificata).
            app(CartManager::class)->addItem('event', $event->id, ['participants' => 1], false);
        } catch (CartValidationException $exception) {
            // Violazione disponibilità (es. capienza esaurita): toast danger, niente pop-up.
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
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

    public function render()
    {
        $event = $this->event();

        return view('livewire.event-detail', [
            'event' => $event,
            'isFav' => $this->isFavorite('event', $event->id),
            'isFree' => $event->is_free,
            'canJoin' => $event->hasJoinCta(),
            // Prezzo nel pop-up: solo eventi acquistabili (prezzo reale, mai il fallback mock);
            // i gratuiti/senza prezzo hanno la CTA Partecipa e il pop-up carrello non esiste.
            'popupPrice' => $event->price_cents !== null ? Format::money($event->price_cents) : null,
            'includedColumns' => [$event->amenityRows('hotel'), $event->amenityRows('animal')],
            'faqs' => $event->faqs,
            'threads' => self::THREADS,
        ])->title('AnimalAmo — '.$event->title);
    }
}
