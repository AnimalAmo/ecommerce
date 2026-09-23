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
 * Benvenuto al partner creato dal pannello. Due varianti:
 *
 *  - account nuovo → link "scegli la password" (broker partner_welcome, 7
 *    giorni): senza, l'account resterebbe con la password casuale di
 *    RegisterPartnerAccount;
 *  - cliente promosso → nessun link password, ha già la sua: solo "il tuo
 *    account ora è anche partner" e il pulsante per l'area partner.
 *
 * NON in coda, come ResetPasswordMail e per lo stesso motivo: senza un
 * queue:work garantito in produzione un benvenuto che non parte lascia il
 * partner senza accesso (e un token a 7 giorni in chiaro in jobs/failed_jobs).
 * Il costo è l'handshake Mailgun dentro la richiesta dell'admin. Si spedisce
 * dopo il commit della registrazione (PartnerAccountService::create). Il
 * partner non ha una lingua salvata: testo e link escono nella lingua di
 * default, come PartnerNewBookingMail.
 */
class PartnerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $partner,
        public ?string $setPasswordUrl,
    ) {
        $this->locale(LaravelLocalization::getDefaultLocale());
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('partner.welcome_mail.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.partner-welcome',
            with: [
                'businessName' => (string) $this->partner->partnerProfile?->business_name,
                'expiresInDays' => intdiv((int) config('auth.passwords.'.PasswordResetService::WELCOME_BROKER.'.expire', 10080), 1440),
                // Nella lingua della mail, non in quella delle rotte registrate dalla richiesta.
                'loginUrl' => (string) LaravelLocalization::getURLFromRouteNameTranslated($this->locale, 'routes.partner.dashboard'),
            ],
        );
    }
}
