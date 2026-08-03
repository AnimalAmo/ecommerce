<?php

namespace App\Mail;

use App\Models\Partner\PartnerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invito a completare l'iscrizione B2B dopo la candidatura "Lavora con noi".
 * Nessun design XD per le email: markdown Laravel brand AnimalAmo.
 *
 * In coda: parte dal submit di WorkWithUs, e con un SMTP reale l'handshake
 * Mailgun (0,5-2s) starebbe dentro la richiesta Livewire — il candidato
 * aspetterebbe la rete, e un errore del provider diventerebbe un errore a
 * schermo su una candidatura invece già salvata. Richiede un queue:work attivo.
 */
class PartnerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartnerApplication $application,
        public string $link,
    ) {
        // Via metodo e non ridichiarando $afterCommit: Queueable la definisce
        // già (untyped, default null) e riscriverla è un fatal error di
        // composizione del trait. Serve il giorno che il submit di WorkWithUs
        // finisca dentro una transazione: il job non deve anticipare il commit.
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('partner.invitation_mail.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.partner-invitation');
    }
}
