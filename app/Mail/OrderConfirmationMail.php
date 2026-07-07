<?php

namespace App\Mail;

use App\Models\Order\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Conferma d'ordine al buyer: righe snapshot + totale (Format::money nella
 * view). Nessun design XD per le email: markdown Laravel brand AnimalAmo,
 * copy in lang/it/orders.php.
 */
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders.mail.confirmation.subject', ['order_number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items', 'payment']);

        return new Content(markdown: 'emails.order-confirmation');
    }
}
