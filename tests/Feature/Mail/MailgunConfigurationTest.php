<?php

namespace Tests\Feature\Mail;

use Tests\TestCase;

/**
 * Guardrail sulla configurazione Mailgun: la regione EU e il timeout SMTP sono
 * scelte che non danno errore se vengono perse — le mail smettono di partire
 * (endpoint US per un dominio EU) o restano appese (timeout null).
 */
class MailgunConfigurationTest extends TestCase
{
    public function test_the_mailgun_mailer_is_registered(): void
    {
        $this->assertSame('mailgun', config('mail.mailers.mailgun.transport'));
    }

    public function test_mailgun_always_talks_https(): void
    {
        $this->assertSame('https', config('services.mailgun.scheme'));
    }

    /**
     * Senza MAILGUN_ENDPOINT nel .env si deve finire in EU, non in US: è la
     * regione dei domini custom del progetto (e quella coerente col GDPR).
     */
    public function test_mailgun_falls_back_to_the_european_region(): void
    {
        $this->assertStringContainsString(
            "env('MAILGUN_ENDPOINT', 'api.eu.mailgun.net')",
            file_get_contents(config_path('services.php')),
        );
    }

    public function test_the_smtp_mailer_always_has_a_timeout(): void
    {
        $timeout = config('mail.mailers.smtp.timeout');

        $this->assertIsInt($timeout);
        $this->assertGreaterThan(0, $timeout);
    }
}
