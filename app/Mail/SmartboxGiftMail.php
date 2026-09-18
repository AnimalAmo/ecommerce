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
                'dedication' => $this->plainText($this->item->options['gift']['dedication'] ?? null),
                'giftMessage' => $this->plainText($this->item->options['gift']['message'] ?? null),
            ],
        );
    }

    /**
     * Dedica e messaggio li scrive l'acquirente, e finiscono dentro un markdown
     * spedito a un indirizzo che non ci ha mai chiesto niente: `[testo](http://…)`
     * uscirebbe come link cliccabile verso un dominio terzo e `![x](…)` come
     * immagine remota, cioè un tracking pixel scelto da altri. Blade neutralizza
     * l'HTML grezzo ma non questa sintassi.
     *
     * Il backslash è l'escape di CommonMark: la parentesi resta visibile nel
     * testo e il link non si forma. Serve a proteggere la reputazione del
     * dominio di invio, non il destinatario: una segnalazione di spam su
     * mg.animalamo.it la pagano anche gli inviti partner.
     */
    private function plainText(?string $text): ?string
    {
        return $text === null ? null : str_replace(['[', ']'], ['\\[', '\\]'], $text);
    }

    private function buyerName(): string
    {
        $order = $this->item->loadMissing('order')->order;

        return trim($order->first_name.' '.$order->last_name);
    }
}
