<?php

namespace App\Mail;

use App\Models\ContactMessage\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica alla casella informazioni di un nuovo messaggio dal form
 * "Contattaci": senza questa mail la riga contact_messages resta a db e
 * nessuno la legge. Nessun design XD per le email: markdown Laravel brand
 * AnimalAmo, copy in lang/it/contact.php.
 *
 * In coda come PartnerInvitationMail: parte dal submit di Contact, e con
 * Mailgun l'handshake (0,5-2s) starebbe dentro la richiesta Livewire — chi
 * scrive aspetterebbe la rete per vedere il "Grazie!". Richiede un queue:work
 * attivo in produzione (con QUEUE_CONNECTION=sync parte comunque, in-process).
 */
class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('contact.notification_mail.subject', ['reason' => $this->contactMessage->reason]),
            // Reply-To e non From: il mittente resta MAIL_FROM_ADDRESS (dominio
            // autenticato SPF/DKIM, altrimenti Mailgun rifiuta o finiamo in
            // spam), ma "Rispondi" scrive direttamente a chi ha compilato.
            replyTo: [new Address($this->contactMessage->email, $this->senderName())],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact-message');
    }

    /** Nome+cognome per l'header Reply-To; i due campi sono obbligatori a form. */
    private function senderName(): string
    {
        return trim("{$this->contactMessage->first_name} {$this->contactMessage->last_name}");
    }
}
