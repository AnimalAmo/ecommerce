<?php

namespace App\Mail;

use App\Models\Partner\PartnerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invito a completare l'iscrizione B2B dopo la candidatura "Lavora con noi".
 * Nessun design XD per le email: markdown Laravel brand AnimalAmo.
 */
class PartnerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartnerApplication $application,
        public string $link,
    ) {}

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
