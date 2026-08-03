<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Link di reimpostazione password. Nessun design XD per le email: markdown
 * Laravel brand AnimalAmo, come PartnerInvitationMail.
 *
 * NON in coda (a differenza di PartnerInvitationMail): senza un queue:work
 * attivo l'invito partner arriva comunque tardi ma l'utente ha già la sua
 * pagina di conferma, mentre un reset password che non parte lascia l'account
 * irraggiungibile e diventa un ticket di assistenza. Il costo è l'handshake
 * Mailgun (0,5-2s) dentro la richiesta Livewire della modale. Il giorno che il
 * worker è garantito in produzione basta aggiungere `implements ShouldQueue`.
 */
class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $link,
        public int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('auth-modal.reset_mail.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reset-password');
    }
}
