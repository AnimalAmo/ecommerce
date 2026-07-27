<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail diagnostica di `php artisan mail:test`: non è contenuto di prodotto,
 * serve solo a provare che le credenziali Mailgun consegnano davvero.
 * Testo non tradotto di proposito, il destinatario è chi gestisce il server.
 */
class MailConfigurationTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** $mailerName, non $mailer: Mailable::$mailer esiste già ed è senza tipo. */
    public function __construct(
        public string $mailerName,
        public string $sentAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[%s] Test configurazione email (%s)', config('app.name'), $this->mailerName),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.mail-configuration-test',
            with: [
                'mailer' => $this->mailerName,
                'environment' => app()->environment(),
                'appUrl' => config('app.url'),
            ],
        );
    }
}
