<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

class EventDetail extends Component
{
    /** Slug evento dalla rotta (es. "brunch-pet-friendly"); il nome differisce dal parametro {event} per non collidere col binding Livewire. */
    public string $eventSlug = '';

    /** Tab attiva ("informazioni" | "discussione"); deep-linkabile via ?tab=discussione. */
    #[Url(except: 'informazioni')]
    public string $tab = 'informazioni';

    /** Visibilità del pop-up "Aggiunto al carrello" (XD: "Pop-up evento acquista"). */
    public bool $cartPopupOpen = false;

    public const TABS = ['informazioni', 'discussione'];

    /**
     * "Cosa è incluso" — due colonne di voci campione come da XD
     * (statiche come nelle pagine sorelle; included: check verde / X rosa).
     */
    public const INCLUDED = [
        [
            ['label' => 'Aria condizionata negli spazi comuni', 'included' => true],
            ['label' => 'Pranzo', 'included' => true],
            ['label' => 'Ascensore', 'included' => true],
            ['label' => 'Wifi', 'included' => true],
            ['label' => 'Noleggio bici', 'included' => false],
            ['label' => 'Spa', 'included' => false],
        ],
        [
            ['label' => 'Dog sitter', 'included' => true],
            ['label' => 'Servizio veterinario', 'included' => true],
            ['label' => 'Omaggio di benvenuto', 'included' => true],
            ['label' => 'Dog Beach nelle vicinanze', 'included' => true],
            ['label' => 'Supplemento animali', 'included' => false],
            ['label' => 'Piscina per cani', 'included' => false],
        ],
    ];

    /**
     * Tab "Discussione" — thread di esempio come da XD (statici come le altre
     * pagine; struttura pronta per essere sostituita da un backend reale).
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
        $entry = collect(Events::EVENTS)->firstWhere('slug', $event);

        abort_unless($entry !== null, 404);

        // Le attività multi-giorno hanno una scheda dedicata (stesso pattern struttura → servizio).
        if ($entry['type'] === 'activity') {
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
        // Evento gratuito: nessun acquisto (XD "Evento gratis - Dettaglio" non ha il bottone
        // "Aggiungi al carrello"), quindi il pop-up carrello non deve poter aprirsi.
        if ($this->isFree()) {
            return;
        }

        // TODO: carrello reale — per ora mostra solo il pop-up di conferma.
        $this->cartPopupOpen = true;
    }

    /** Variante gratuita (price "Gratis") → artboard XD "Evento gratis - Dettaglio". */
    private function isFree(): bool
    {
        $entry = collect(Events::EVENTS)->firstWhere('slug', $this->eventSlug);

        return ($entry['price'] ?? null) === 'Gratis';
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function render()
    {
        $event = collect(Events::EVENTS)->firstWhere('slug', $this->eventSlug);

        return view('livewire.event-detail', [
            'event' => $event,
            'isFree' => $this->isFree(),
            // Prezzo nel pop-up: solo la parte numerica ("25 € a persona" → "25 €"); fallback fisso come i dati dei pop-up fratelli (valore XD).
            'popupPrice' => str_replace(' a persona', '', $event['price'] ?? '25 €'),
            'includedColumns' => self::INCLUDED,
            'threads' => self::THREADS,
        ])->title('AnimalAmo — '.$event['title']);
    }
}
