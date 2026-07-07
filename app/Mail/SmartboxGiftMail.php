<?php

namespace App\Mail;

use App\Models\OrderItem\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail al destinatario della smartbox regalo: titolo del cofanetto, chi lo
 * regala, dedica/messaggio dalle options.gift e validità (booked_until della
 * riga = oggi + validity_months). "message" resta fuori dalle view variables:
 * il nome è riservato al Mailable nei template mail.
 */
class SmartboxGiftMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OrderItem $item,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders.mail.gift.subject', ['buyer' => $this->buyerName()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.smartbox-gift',
            with: [
                'buyerName' => $this->buyerName(),
                'dedication' => $this->item->options['gift']['dedication'] ?? null,
                'giftMessage' => $this->item->options['gift']['message'] ?? null,
            ],
        );
    }

    private function buyerName(): string
    {
        $order = $this->item->loadMissing('order')->order;

        return trim($order->first_name.' '.$order->last_name);
    }
}
