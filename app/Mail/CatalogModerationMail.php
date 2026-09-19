<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Esito della moderazione di una scheda, al partner che l'ha pubblicata:
 * approvata (è online) o rimandata indietro con le modifiche da fare.
 */
class CatalogModerationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const APPROVED = 'approved';

    public const CHANGES_REQUESTED = 'changes_requested';

    public function __construct(
        public string $outcome,
        public string $partnerName,
        public string $itemName,
        public ?string $note,
        public string $link,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("moderation.mail.{$this->outcome}.subject", ['name' => $this->itemName]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.catalog-moderation');
    }
}
