<?php

namespace App\Mail;

use App\Models\Order\Order;
use App\Models\Partner\PartnerProfile;
use App\Services\Partner\PartnerPaymentModeService;
use App\Support\SafeUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Conferma d'ordine al buyer: righe snapshot + totale (Format::money nella
 * view). Nessun design XD per le email: markdown Laravel brand AnimalAmo,
 * copy in lang/it/orders.php.
 *
 * Variante "da pagare in struttura" (order->isOnSite()): oggetto e titolo da
 * prenotazione, importo da pagare al partner, suo indirizzo e link al sito
 * copiato sull'ordine. La modalità si legge dall'ordine e mai dal partner, che
 * può cambiarla dopo; il profilo serve solo per nome e indirizzo.
 */
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function envelope(): Envelope
    {
        $copy = $this->order->isOnSite() ? 'orders.mail.on_site' : 'orders.mail.confirmation';

        return new Envelope(
            subject: __($copy.'.subject', ['order_number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items', 'payment']);

        $profile = $this->order->isOnSite() ? $this->sellerProfile() : null;

        return new Content(
            markdown: 'emails.order-confirmation',
            with: [
                'partnerName' => $profile === null ? null : $this->partnerName($profile),
                'partnerAddress' => $profile === null ? null : $this->partnerAddress($profile),
                // Solo http(s): una copia sull'ordine scritta senza passare dal service non arriva all'href.
                'partnerPaymentUrl' => $this->order->isOnSite() ? SafeUrl::http($this->order->partner_payment_url) : null,
            ],
        );
    }

    /** Un ordine ha un solo venditore (CartManager::guardSinglePartner): basta la prima riga. */
    private function sellerProfile(): ?PartnerProfile
    {
        return app(PartnerPaymentModeService::class)->profileFor($this->order->items->first()?->partner_user_id);
    }

    /**
     * Ragione sociale, poi nome e cognome. Niente di tutto questo (o profilo
     * sparito) = null, e la vista usa le diciture senza nome invece di
     * stampare "al partner , in struttura".
     */
    private function partnerName(PartnerProfile $profile): ?string
    {
        $name = $profile->business_name ?: trim($profile->user?->first_name.' '.$profile->user?->last_name);

        return $name === '' ? null : $name;
    }

    /** "Via Roma 1, 25121 Brescia (BS)": i pezzi mancanti si saltano, mai virgole appese. */
    private function partnerAddress(PartnerProfile $profile): ?string
    {
        $locality = trim(implode(' ', array_filter([$profile->zip, $profile->city], 'filled')));

        if (filled($profile->province)) {
            $locality = trim($locality.' ('.$profile->province.')');
        }

        $address = implode(', ', array_filter([$profile->address, $locality], 'filled'));

        return $address === '' ? null : $address;
    }
}
