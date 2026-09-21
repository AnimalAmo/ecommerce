<?php

namespace App\Services\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Cosa un evento Mailgun cambia nella newsletter, accanto alla riga di
 * mail_deliveries che scrive RecordMailgunEvent:
 *
 * - rimbalzo definitivo o segnalazione di spam → l'indirizzo entra nella
 *   lista di soppressione, qualunque mail l'abbia provocato (se non esiste per
 *   una conferma d'ordine, non esiste nemmeno per la newsletter);
 * - disiscrizione dal link di Mailgun → disiscritto anche qui;
 * - consegna e apertura di una mail di campagna → contatori dell'invio,
 *   contando ogni destinatario una volta sola.
 */
class NewsletterFeedback
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * @param  array<string, mixed>  $event  il blocco `event-data` del payload
     * @return bool se l'evento ha cambiato qualcosa nella newsletter
     */
    public function handle(array $event): bool
    {
        $type = (string) ($event['event'] ?? '');

        return match (true) {
            $type === 'failed' && ($event['severity'] ?? null) === 'permanent' => $this->permanentFailure($event),
            $type === 'complained' => $this->suppress($event, NewsletterSubscriber::STATUS_COMPLAINED),
            $type === 'unsubscribed' => $this->unsubscribe($event),
            $type === 'delivered' => $this->stamp($event, 'delivered_at', 'delivered_count'),
            $type === 'opened' => $this->stamp($event, 'opened_at', 'opened_count'),
            default => false,
        };
    }

    /**
     * Scarto definitivo. Mailgun usa lo stesso evento anche quando non tenta
     * nemmeno la consegna perché l'indirizzo è già nelle sue liste: lì il
     * motivo dice quale, e un "disiscritto" non è un rimbalzo.
     *
     * @param  array<string, mixed>  $event
     */
    private function permanentFailure(array $event): bool
    {
        return match ($event['reason'] ?? null) {
            'suppress-unsubscribe' => $this->unsubscribe($event),
            'suppress-complaint' => $this->suppress($event, NewsletterSubscriber::STATUS_COMPLAINED),
            default => $this->suppress($event, NewsletterSubscriber::STATUS_BOUNCED),
        };
    }

    /** @param  array<string, mixed>  $event */
    private function suppress(array $event, string $status): bool
    {
        $subscriber = $this->subscriberFor($event);

        if ($subscriber === null || $subscriber->isSuppressed()) {
            return false;
        }

        $this->subscriptions->suppress($subscriber, $status);

        return true;
    }

    /** @param  array<string, mixed>  $event */
    private function unsubscribe(array $event): bool
    {
        $subscriber = $this->subscriberFor($event);

        if ($subscriber === null || $subscriber->isSuppressed() || $subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            return false;
        }

        $this->subscriptions->unsubscribe($subscriber);

        return true;
    }

    /**
     * Primo evento di quel tipo per il destinatario: timbra la riga e sale il
     * contatore. Gli eventi ripetuti (più aperture, webhook ritentati) non
     * contano: l'aggiornamento è condizionato alla colonna ancora vuota.
     *
     * @param  array<string, mixed>  $event
     */
    private function stamp(array $event, string $column, string $counter): bool
    {
        $recipient = $this->recipientFor($event);

        if ($recipient === null) {
            return false;
        }

        $at = isset($event['timestamp']) ? Carbon::createFromTimestamp((float) $event['timestamp']) : now();

        // toBase(): senza toccare updated_at, che per CampaignSender è
        // l'ultima attività dell'invio. Le aperture arrivano per giorni, e
        // farebbero sembrare viva una catena ferma.
        $first = NewsletterCampaignRecipient::whereKey($recipient->getKey())
            ->whereNull($column)
            ->toBase()
            ->update([$column => $at]) === 1;

        if ($first) {
            NewsletterCampaign::whereKey($recipient->newsletter_campaign_id)->increment($counter);
        }

        return $first;
    }

    /**
     * La variabile v:newsletter_recipient_id prima (la mette la mail di
     * campagna), il message-id salvato all'invio come ripiego.
     *
     * @param  array<string, mixed>  $event
     */
    private function recipientFor(array $event): ?NewsletterCampaignRecipient
    {
        $id = data_get($event, 'user-variables.newsletter_recipient_id');

        if (is_numeric($id)) {
            $recipient = NewsletterCampaignRecipient::find((int) $id);

            // La variabile arriva dal contenuto della mail: deve combaciare
            // con l'indirizzo dell'evento.
            if ($recipient !== null && Str::lower((string) $recipient->subscriber?->email) === Str::lower((string) ($event['recipient'] ?? ''))) {
                return $recipient;
            }
        }

        $messageId = trim((string) data_get($event, 'message.headers.message-id'), '<>');

        return $messageId === ''
            ? null
            : NewsletterCampaignRecipient::firstWhere('message_id', $messageId);
    }

    /** @param  array<string, mixed>  $event */
    private function subscriberFor(array $event): ?NewsletterSubscriber
    {
        $email = Str::lower(trim((string) ($event['recipient'] ?? '')));

        return $email === '' ? null : NewsletterSubscriber::firstWhere('email', $email);
    }
}
