<?php

namespace App\Mail;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Nuova prenotazione, al partner che la riceve: solo le sue righe, il loro
 * totale e se i soldi sono già arrivati (online) o li incassa lui (in
 * struttura). Non implementa ShouldQueue: parte da un listener già in coda,
 * e accodarla di nuovo la toglierebbe dal try/catch di sendSilently.
 *
 * Il partner non ha una lingua salvata: la mail esce sempre nella lingua di
 * default, testo e link insieme. Senza questo prenderebbe la lingua di chi
 * compra (coda sync, il listener gira nella richiesta del checkout) o quella
 * del worker, e con route() il link resterebbe nella lingua delle rotte
 * registrate dalla richiesta, anche diversa da quella del testo.
 */
class PartnerNewBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly User $partner,
    ) {
        $this->locale(LaravelLocalization::getDefaultLocale());
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders.mail.partner_booking.subject', ['order_number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        $lines = $this->order->loadMissing('items')->items
            ->where('partner_user_id', $this->partner->id)
            ->values();

        return new Content(
            markdown: 'emails.partner-new-booking',
            with: [
                'greetingName' => $this->greetingName(),
                'lines' => $lines,
                'linesTotal' => (int) $lines->sum('price_cents'),
                // Il dettaglio è per riga: con più righe si apre la prima, l'elenco mostra le altre.
                'link' => $lines->isEmpty()
                    ? $this->localizedUrl('routes.partner.bookings')
                    : $this->localizedUrl('routes.partner.bookings.show', ['booking' => $lines->first()->id]),
            ],
        );
    }

    /** Nome, poi ragione sociale (come CatalogModerationMail); null = saluto senza nome, mai "Ciao ,". */
    private function greetingName(): ?string
    {
        $name = $this->partner->first_name ?: $this->partner->partnerProfile?->business_name;

        return filled($name) ? $name : null;
    }

    /**
     * URL nella lingua della mail, non in quella delle rotte registrate dalla
     * richiesta corrente (come NewsletterUrls).
     *
     * @param  array<string, mixed>  $parameters
     */
    private function localizedUrl(string $key, array $parameters = []): string
    {
        return (string) LaravelLocalization::getURLFromRouteNameTranslated($this->locale, $key, $parameters);
    }
}
