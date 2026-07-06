<?php

namespace App\Livewire;

use Livewire\Component;

class ProfileOrderSummary extends Component
{
    /** Id ordine dalla rotta (mock: il contenuto è sempre quello dell'artboard XD). */
    public string $order = '';

    /** Ordine passato → variante XD "– 1": box 206px con "Scrivi una recensione". */
    public bool $past = false;

    public function mount(): void
    {
        $this->past = in_array($this->order, array_column(ProfileOrders::ORDERS['passati'], 'id'), true);
    }

    /**
     * Articoli dell'ordine come da XD "Profilo – i miei ordini – riepilogo":
     * card "Box preferiti" senza cuore/borsa, prezzo riga fisso "0,00 €" da mock.
     * Il secondo articolo (smartbox) non ha la riga date, come nel carrello regalo.
     * NOTA: l'artboard "– 1" (ordine passato) ripete gli stessi 3 articoli anche se
     * l'ordine passato in lista ne ha 2 — copy-paste del designer, mock mantenuto.
     */
    // TODO: ordine reale da backend
    public const ITEMS = [
        [
            'id' => 'hotel-brescia',
            'title' => 'Hotel Brescia',
            'tag' => 'Strutture',
            'tagColor' => '#FF9F3E',
            'photo' => 'cart-hotel-brescia.jpg',
            'location' => 'Dario Boario Terme (BS), Italia',
            'dates' => '17/02/2024 - 22/02/2024',
            'guests' => '2 adulti',
            'dogs' => '1 cane',
            'price' => '0,00 €',
        ],
        [
            'id' => 'weekend-piemonte',
            'title' => 'Weekend in Piemonte',
            'tag' => 'Soggiorno',
            'tagColor' => '#8DE0FF',
            'photo' => 'cart-weekend-piemonte.jpg',
            'location' => 'Torino, Italia',
            'dates' => null,
            'guests' => '2 adulti',
            'dogs' => '1 cane',
            'price' => '0,00 €',
        ],
        [
            'id' => 'weekend-escursioni',
            'title' => 'Weekend di escursioni',
            'tag' => 'Attività',
            'tagColor' => '#8E53E6',
            'photo' => 'cart-excursions-viareggio.jpg',
            'location' => 'Viareggio, Italia',
            'dates' => '17/02/2024 - 22/02/2024',
            'guests' => '2 adulti',
            'dogs' => '1 cane',
            'price' => '0,00 €',
        ],
    ];

    public function render()
    {
        return view('livewire.profile-order-summary', [
            'items' => self::ITEMS,
        ])->title('Riepilogo ordine — AnimalAmo');
    }
}
