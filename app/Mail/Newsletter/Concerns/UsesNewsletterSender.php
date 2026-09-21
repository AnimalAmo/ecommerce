<?php

namespace App\Mail\Newsletter\Concerns;

use Closure;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Mittente e Reply-To della newsletter (config/newsletter.php), quando sono
 * configurati; altrimenti restano quelli della posta di servizio.
 *
 * Callback Symfony e non Envelope: mail.reply_to è un alwaysReplyTo, e il
 * Reply-To di un mailable gli si somma invece di sostituirlo (vedi
 * ContactMessageMail). Chi risponde a una newsletter deve scrivere a una
 * casella sola.
 */
trait UsesNewsletterSender
{
    protected function newsletterSender(): Closure
    {
        return function (Email $message): void {
            $from = config('newsletter.from.address');

            if (filled($from)) {
                $message->from(new Address($from, (string) (config('newsletter.from.name') ?? config('mail.from.name'))));
            }

            $replyTo = config('newsletter.reply_to');

            if (filled($replyTo)) {
                $message->replyTo(new Address($replyTo));
            }
        };
    }
}
