<?php

namespace App\Services\Newsletter;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;

/**
 * Il solo punto da cui partono le mail della newsletter (conferme e
 * campagne): sul mailer della newsletter, se configurato, invece che su
 * quello della posta di servizio.
 *
 * Mail::mailer() e non Mailable::mailer(): Mail::to() spedisce con il mailer
 * di default e riscrive quello dichiarato dal mailable.
 */
class NewsletterMailer
{
    /** Null quando il mailable va in coda (ShouldQueue). */
    public function send(string $email, Mailable $mail): ?SentMessage
    {
        $result = Mail::mailer(config('newsletter.mailer'))->to($email)->send($mail);

        return $result instanceof SentMessage ? $result : null;
    }
}
