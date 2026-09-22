<?php

namespace App\Mail;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Nuova prenotazione, al partner che la riceve: solo le sue righe, il loro
 * totale e se i soldi sono già arrivati (online) o li incassa lui (in
 * struttura). Non implementa ShouldQueue: parte da un listener già in coda,
 * e accodarla di nuovo la toglierebbe dal try/catch di sendSilently.
 */
class PartnerNewBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly User $partner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders.mail.partner_booking.subject', ['order_number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        $lines = $this->order->loadMissing('items')->items
            ->where('partner_user_id', $this->partner->id)
            ->values();

        return new Content(
            markdown: 'emails.partner-new-booking',
            with: [
                'lines' => $lines,
                'linesTotal' => (int) $lines->sum('price_cents'),
                // Il dettaglio è per riga: con più righe si apre la prima, l'elenco mostra le altre.
                'link' => $lines->isEmpty()
                    ? route('partner.bookings')
                    : route('partner.bookings.show', ['booking' => $lines->first()->id]),
            ],
        );
    }
}
