<?php

namespace App\Mail\Newsletter;

use App\Mail\Newsletter\Concerns\UsesNewsletterSender;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\HtmlSanitizer;
use App\Services\Newsletter\NewsletterUrls;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Symfony\Component\Mime\Email;

/**
 * Un numero della newsletter per un destinatario.
 *
 * Lingua: quella dell'iscritto se la campagna ne ha la versione (oggetto e
 * testo), altrimenti tutta in italiano — mai oggetto in una lingua e testo
 * nell'altra.
 *
 * NON in coda di suo: la coda è il lotto (SendCampaignBatch), che spedisce in
 * sincrono per poter segnare `sent` solo dopo che il transport ha accettato
 * la mail e salvarne il message-id.
 *
 * Senza iscritto è l'invio di prova (avviso in testa) o l'anteprima del
 * pannello: nessun header di disiscrizione.
 *
 * Tag e variabili Mailgun servono al webhook per attribuire consegne e
 * aperture a campagna e destinatario anche senza il message-id.
 */
class NewsletterCampaignMail extends Mailable
{
    use Queueable, UsesNewsletterSender;

    public string $contentLocale;

    public function __construct(
        public NewsletterCampaign $campaign,
        string $locale,
        public ?NewsletterSubscriber $subscriber = null,
        public ?NewsletterCampaignRecipient $recipient = null,
        public bool $preview = false,
    ) {
        $this->contentLocale = $campaign->hasVersion($locale) ? $locale : 'it';
        $this->locale($this->contentLocale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
            tags: $this->tags(),
            metadata: $this->variables(),
            using: [$this->newsletterSender(), $this->mailgunOptions()],
        );
    }

    /**
     * RFC 8058: Gmail e Yahoo mostrano "Annulla iscrizione" e chiamano in POST
     * l'URL https con il corpo `List-Unsubscribe=One-Click`.
     */
    public function headers(): Headers
    {
        if ($this->subscriber === null) {
            return new Headers;
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<'.app(NewsletterUrls::class)->oneClick($this->subscriber).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        $urls = app(NewsletterUrls::class);
        $body = (string) $this->campaign->getTranslation('body', $this->contentLocale);
        $linkLocale = $this->subscriber?->locale ?? $this->contentLocale;

        return new Content(
            markdown: 'emails.newsletter.campaign',
            text: 'emails.newsletter.campaign-text',
            with: [
                'preheader' => (string) $this->campaign->getTranslation('preheader', $this->contentLocale),
                // Su una riga sola, dentro un <div>: per il parser markdown del
                // layout è un unico blocco HTML, che passa intatto.
                'bodyHtml' => '<div>'.preg_replace('/\R+/', ' ', trim($body)).'</div>',
                'bodyText' => HtmlSanitizer::toText($body),
                'homeUrl' => $urls->home($linkLocale),
                'privacyUrl' => $urls->privacy($linkLocale),
                'unsubscribeUrl' => match (true) {
                    $this->subscriber !== null => $urls->unsubscribePage($this->subscriber),
                    // Prova e anteprima: il piede resta identico a quello vero,
                    // il link porta alla home e l'avviso in testa lo dice.
                    default => $urls->home($linkLocale),
                },
                'isTest' => $this->subscriber === null && ! $this->preview,
            ],
        );
    }

    private function subjectLine(): string
    {
        return (string) $this->campaign->getTranslation('subject', $this->contentLocale);
    }

    /** @return list<string> */
    private function tags(): array
    {
        return $this->subscriber === null
            ? ['newsletter-test']
            : ['newsletter', 'newsletter-campaign-'.$this->campaign->getKey()];
    }

    /** @return array<string, string> */
    private function variables(): array
    {
        $variables = ['newsletter_campaign_id' => (string) $this->campaign->getKey()];

        if ($this->recipient !== null) {
            $variables['newsletter_recipient_id'] = (string) $this->recipient->getKey();
        }

        return $variables;
    }

    /**
     * Aperture tracciate solo sulle campagne: il tracking del dominio resta
     * spento per la posta di servizio. Con il transport API di Mailgun tag e
     * variabili passano già dall'Envelope; via SMTP servono gli header
     * X-Mailgun-*, che Mailgun legge al posto dei parametri o:/v:.
     */
    private function mailgunOptions(): Closure
    {
        return function (Email $message): void {
            $mailer = config('newsletter.mailer') ?? config('mail.default');
            $headers = $message->getHeaders();

            if (config("mail.mailers.{$mailer}.transport") === 'mailgun') {
                $headers->addTextHeader('o:tracking-opens', 'yes');

                return;
            }

            $headers->addTextHeader('X-Mailgun-Track-Opens', 'yes');
            $headers->addTextHeader('X-Mailgun-Variables', (string) json_encode($this->variables()));

            foreach ($this->tags() as $tag) {
                $headers->addTextHeader('X-Mailgun-Tag', $tag);
            }
        };
    }
}
