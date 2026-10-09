<?php

namespace App\Mail;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Link "imposta una nuova password" mandato dall'amministratore
 * (AdminPasswordLinkService). Nessuna password nel testo: l'utente la sceglie
 * dal link.
 *
 * NON in coda, come ResetPasswordMail e PartnerWelcomeMail: senza un worker
 * garantito l'utente resterebbe senza accesso. Gli iscritti non hanno una
 * lingua salvata: testo e link escono nella lingua di default.
 */
class AdminPasswordLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $expiresInDays;

    public function __construct(
        public User $user,
        public string $link,
    ) {
        $this->locale(LaravelLocalization::getDefaultLocale());
        $this->expiresInDays = intdiv((int) config('auth.passwords.'.PasswordResetService::ADMIN_BROKER.'.expire', 10080), 1440);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('auth-modal.admin_reset_mail.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.admin-password-link');
    }
}
