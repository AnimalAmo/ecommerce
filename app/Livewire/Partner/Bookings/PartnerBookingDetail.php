<?php

namespace App\Livewire\Partner\Bookings;

use Livewire\Component;

/**
 * Dettaglio prenotazione (XD "Dettaglio prenotazione strutture"): info cliente
 * + info prenotazione su due colonne, foto struttura e bottone Stampa.
 * Dati DEMO fedeli al mockup come la lista Prenotazioni: quando il checkout
 * B2C scriverà prenotazioni sui servizi partner, la fonte diventerà l'Order
 * (+ OrderItem) risolto dall'id di prenotazione.
 */
class PartnerBookingDetail extends Component
{
    public string $booking = '';

    public function mount(string $booking): void
    {
        $this->booking = $booking;
    }

    public function render()
    {
        return view('livewire.partner.bookings.detail', [
            'customer' => [
                'first_name' => 'Giulia',
                'last_name' => 'Rossi',
                'email' => 'giulia.rossi@gmail.com',
                'phone' => '3487384989',
            ],
            'info' => [
                // Colonna sinistra del mockup.
                'left' => [
                    'detail_id' => $this->booking,
                    'detail_payment_method' => 'Bonifico bancario',
                    'detail_booking_date' => '24/07/24',
                    'detail_time' => '17:00',
                    'detail_language' => 'Italiano, Inglese',
                ],
                // Colonna destra del mockup.
                'right' => [
                    'detail_structure' => 'Hotel Brescia',
                    'detail_price' => '135 €',
                    'detail_people' => '2',
                    'detail_duration' => '2 ore',
                ],
            ],
        ])->title(__('partner.bookings.detail_title'));
    }
}
