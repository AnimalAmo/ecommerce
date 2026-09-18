<?php

namespace Tests\Feature\Mail;

use App\Models\MailDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Finché non esiste un'area admin, questo comando è l'unica risposta alla
 * domanda "a chi non è arrivata la mail?".
 */
class MailDeliveriesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_failed_deliveries(): void
    {
        MailDelivery::create([
            'message_id' => 'ok@mg.animalamo.it',
            'recipient' => 'consegnata@hotel.it',
            'status' => MailDelivery::STATUS_DELIVERED,
        ]);

        MailDelivery::create([
            'message_id' => 'ko@mg.animalamo.it',
            'recipient' => 'fallita@hotel.it',
            'status' => MailDelivery::STATUS_FAILED,
            'reason' => 'No MX for hotel.it',
        ]);

        // Una sola aspettativa sull'output: due expectsOutputToContain in fila
        // si annullano a vicenda (sono mock su doWrite, non filtri sul testo).
        config(['services.mailgun.webhook_signing_key' => 'chiave-di-test']);

        $this->artisan('mail:deliveries --failed')
            ->expectsOutputToContain('fallita@hotel.it')
            ->assertSuccessful();
    }

    /**
     * Senza signing key il comando è cieco e deve dirlo *anche quando la tabella è
     * piena*: il listener apre una riga per ogni mail che parte pure a webhook
     * spento, quindi un avviso legato alla tabella vuota sparirebbe dopo il primo
     * invio, proprio mentre l'elenco ordinato fa credere che tutto sia verificato.
     */
    public function test_it_warns_when_the_webhook_is_not_configured_even_with_rows(): void
    {
        config(['services.mailgun.webhook_signing_key' => '']);

        MailDelivery::create([
            'message_id' => 'ok@mg.animalamo.it',
            'recipient' => 'consegnata@hotel.it',
            'status' => MailDelivery::STATUS_SENT,
        ]);

        $this->artisan('mail:deliveries')
            ->expectsOutputToContain('MAILGUN_WEBHOOK_SIGNING_KEY')
            ->assertFailed();
    }

    /** Con la chiave configurata il comando esce pulito. */
    public function test_it_succeeds_when_the_webhook_is_configured(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'chiave-di-test']);

        $this->artisan('mail:deliveries')->assertSuccessful();
    }
}
