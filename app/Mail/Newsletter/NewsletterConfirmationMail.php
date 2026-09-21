<?php

namespace App\Mail\Newsletter;

use App\Mail\Newsletter\Concerns\UsesNewsletterSender;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail del double opt-in: il link porta alla pagina di conferma. `courtesy`
 * è la variante per i contatti della vecchia casella di registrazione, che
 * non hanno compilato nessun form e vanno avvisati del perché ricevono la mail.
 *
 * In coda come PartnerInvitationMail: parte dal form del sito, e l'attesa di
 * Mailgun non deve stare dentro la richiesta.
 */
class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesNewsletterSender;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public bool $courtesy = false,
    ) {
        $this->locale($subscriber->locale);
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('newsletter.mail.confirm.subject'),
            tags: ['newsletter-confirmation'],
            using: [$this->newsletterSender()],
        );
    }

    public function content(): Content
    {
        $urls = app(NewsletterUrls::class);

        return new Content(
            markdown: 'emails.newsletter.confirmation',
            text: 'emails.newsletter.confirmation-text',
            with: [
                'preheader' => __('newsletter.mail.confirm.preheader'),
                'confirmUrl' => $urls->confirm($this->subscriber),
                'homeUrl' => $urls->home($this->subscriber->locale),
                'privacyUrl' => $urls->privacy($this->subscriber->locale),
                'unsubscribeUrl' => null,
                'ttlDays' => (int) config('newsletter.confirmation_ttl_days'),
            ],
        );
    }
}
