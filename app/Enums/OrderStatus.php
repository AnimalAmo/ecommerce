<?php

namespace App\Enums;

/**
 * Stato ordine (capture-first: nasce Pending nella pipeline e passa a Paid
 * nella stessa transaction quando il pagamento catturato viene registrato).
 *
 * Confirmed = prenotazione valida senza incasso su AnimalAmo: il cliente paga
 * il partner in struttura o sul suo sito. Vale come prenotazione, non come
 * soldi: le query "prenotazione valida" usano bookingStatuses(), quelle sugli
 * incassi restano su Paid.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('orders.status.'.$this->value);
    }

    /**
     * Stati che contano come prenotazione fatta (venduti, blocco eliminazione,
     * prenotazioni ricevute).
     *
     * @return list<self>
     */
    public static function bookingStatuses(): array
    {
        return [self::Paid, self::Confirmed];
    }
}
