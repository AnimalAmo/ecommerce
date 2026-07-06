<?php

namespace App\Livewire;

use Flux\Flux;
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

    /** Articolo in recensione nel pop-up (XD "Pop-up scrivi recensione"); null = chiuso. */
    public ?string $reviewItemId = null;

    public string $reviewTitle = '';

    public string $reviewText = '';

    /** "Scrivi una recensione": apre il pop-up con i campi azzerati. */
    public function openReview(string $itemId): void
    {
        if (in_array($itemId, array_column(self::ITEMS, 'id'), true)) {
            $this->reviewItemId = $itemId;
            $this->reviewTitle = '';
            $this->reviewText = '';

            Flux::modal('scrivi-recensione')->show();
        }
    }

    /** "Annulla": chiude il pop-up scartando i campi. */
    public function closeReview(): void
    {
        $this->reviewItemId = null;

        Flux::modal('scrivi-recensione')->close();
    }

    /** "Conferma": chiude e basta — mock senza backend. */
    public function confirmReview(): void
    {
        // TODO: invio recensione backend
        $this->closeReview();
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
        $reviewItem = null;

        foreach (self::ITEMS as $item) {
            if ($item['id'] === $this->reviewItemId) {
                $reviewItem = $item;
            }
        }

        return view('livewire.profile-order-summary', [
            'items' => self::ITEMS,
            'reviewItem' => $reviewItem,
        ])->title('Riepilogo ordine — AnimalAmo');
    }
}
