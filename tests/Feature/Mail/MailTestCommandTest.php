<?php

namespace Tests\Feature\Mail;

use App\Mail\MailConfigurationTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * `php artisan mail:test`: è lo strumento con cui si verificano le credenziali
 * Mailgun in staging/produzione, quindi deve restare onesto anche quando la
 * configurazione è incompleta — mai un exit code 0 su un invio fallito, mai un
 * segreto stampato a video.
 */
class MailTestCommandTest extends TestCase
{
    // ── Percorso felice ──────────────────────────────────────────────────────

    public function test_it_sends_the_diagnostic_mail_with_the_default_mailer(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it'])
            ->assertExitCode(Command::SUCCESS);

        Mail::assertSent(
            MailConfigurationTestMail::class,
            fn (MailConfigurationTestMail $mail): bool => $mail->hasTo('ops@animalamo.it')
                && $mail->mailerName === config('mail.default')
        );
    }

    public function test_it_reports_the_effective_transport_settings(): void
    {
        Mail::fake();

        config([
            'mail.mailers.smtp.host' => 'smtp.eu.mailgun.org',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'postmaster@mg.animalamo.it',
        ]);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'smtp'])
            ->expectsOutputToContain('smtp.eu.mailgun.org')
            ->expectsOutputToContain('587')
            ->expectsOutputToContain('postmaster@mg.animalamo.it')
            ->assertExitCode(Command::SUCCESS);
    }

    // ── Sicurezza dell'output ────────────────────────────────────────────────

    public function test_it_never_prints_the_smtp_password(): void
    {
        Mail::fake();

        config([
            'mail.mailers.smtp.username' => 'postmaster@mg.animalamo.it',
            'mail.mailers.smtp.password' => 'super-secret-mailgun-password',
        ]);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'smtp'])
            ->doesntExpectOutputToContain('super-secret-mailgun-password')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_never_prints_the_mailgun_api_secret(): void
    {
        Mail::fake();

        config([
            'services.mailgun.domain' => 'mg.animalamo.it',
            'services.mailgun.secret' => 'key-super-secret-api-key',
        ]);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'mailgun'])
            ->doesntExpectOutputToContain('key-super-secret-api-key')
            ->expectsOutputToContain('mg.animalamo.it')
            ->assertExitCode(Command::SUCCESS);
    }

    // ── Avvisi su configurazioni che "riescono" senza consegnare ─────────────

    public function test_it_warns_when_the_transport_does_not_leave_the_machine(): void
    {
        Mail::fake();

        config(['mail.default' => 'log']);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it'])
            ->expectsOutputToContain('non invia nulla')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_warns_when_smtp_credentials_are_missing(): void
    {
        Mail::fake();

        config(['mail.mailers.smtp.username' => null]);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'smtp'])
            ->expectsOutputToContain('Nessuna credenziale SMTP')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_warns_when_the_from_address_is_still_a_placeholder(): void
    {
        Mail::fake();

        config(['mail.from.address' => 'hello@example.com']);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it'])
            ->expectsOutputToContain('indirizzo di esempio')
            ->assertExitCode(Command::SUCCESS);
    }

    // ── Input non valido: si ferma prima di toccare il mailer ────────────────

    public function test_it_rejects_a_malformed_recipient(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['recipient' => 'non-e-una-email'])
            ->assertExitCode(Command::INVALID);

        Mail::assertNothingSent();
    }

    public function test_it_rejects_a_mailer_missing_from_the_configuration(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'inesistente'])
            ->expectsOutputToContain('non definito')
            ->assertExitCode(Command::INVALID);

        Mail::assertNothingSent();
    }

    // ── Fallimento del transport ─────────────────────────────────────────────

    public function test_it_fails_loudly_when_the_transport_rejects_the_message(): void
    {
        // Niente Mail::fake() qui: serve che l'invio arrivi davvero al transport.
        Mail::extend('exploding', fn (): AbstractTransport => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('535 5.7.8 Authentication failed');
            }

            public function __toString(): string
            {
                return 'exploding://';
            }
        });

        config(['mail.mailers.exploding' => ['transport' => 'exploding']]);

        $this->artisan('mail:test', ['recipient' => 'ops@animalamo.it', '--mailer' => 'exploding'])
            ->expectsOutputToContain('535 5.7.8 Authentication failed')
            ->assertExitCode(Command::FAILURE);
    }
}
