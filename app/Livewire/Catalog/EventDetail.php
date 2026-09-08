<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use App\Support\Format;
use Flux\Flux;
use Livewire\Component;

class EventDetail extends Component
{
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

        return view('livewire.catalog.event-detail', [
            'event' => $event,
            'isFav' => $this->isFavorite('event', $event->id),
            'isFree' => $event->is_free,
            'canJoin' => $event->hasJoinCta(),
            // Prezzo nel pop-up: solo eventi acquistabili (prezzo reale, mai il fallback mock);
            // i gratuiti/senza prezzo hanno la CTA Partecipa e il pop-up carrello non esiste.
            'popupPrice' => $event->price_cents !== null ? Format::money($event->price_cents) : null,
            'includedColumns' => [$event->amenityRows('hotel'), $event->amenityRows('animal')],
            'faqs' => $event->faqs,
        ])->title('AnimalAmo — '.$event->title);
    }
}
