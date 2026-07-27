<?php

namespace App\Console\Commands;

use App\Mail\MailConfigurationTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Verifica end-to-end della configurazione email (Mailgun SMTP o API).
 * Stampa i parametri che il framework sta davvero usando prima di inviare:
 * senza questo, l'unico modo per accorgersi di una credenziale sbagliata è
 * un ordine reale che non riceve la conferma.
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test
                            {recipient : Indirizzo email a cui inviare la prova}
                            {--mailer= : Mailer da usare, se diverso da quello di default}';

    protected $description = 'Invia una mail di prova per verificare la configurazione SMTP/Mailgun';

    public function handle(): int
    {
        $recipient = (string) $this->argument('recipient');

        if (Validator::make(['email' => $recipient], ['email' => 'required|email'])->fails()) {
            $this->components->error("Destinatario non valido: {$recipient}");

            return self::INVALID;
        }

        $mailer = (string) ($this->option('mailer') ?: config('mail.default'));

        if (! config()->has("mail.mailers.{$mailer}")) {
            $this->components->error("Mailer \"{$mailer}\" non definito in config/mail.php.");

            return self::INVALID;
        }

        $this->components->twoColumnDetail('<fg=gray>Parametro</>', '<fg=gray>Valore</>');

        foreach ($this->settingsFor($mailer) as $label => $value) {
            $this->components->twoColumnDetail($label, $value);
        }

        $this->newLine();

        foreach ($this->warningsFor($mailer) as $warning) {
            $this->components->warn($warning);
        }

        $sentAt = Carbon::now()->format('d/m/Y H:i:s T');

        try {
            Mail::mailer($mailer)
                ->to($recipient)
                ->send(new MailConfigurationTestMail($mailer, $sentAt));
        } catch (Throwable $exception) {
            // Il messaggio del transport è la diagnosi vera (auth fallita, dominio
            // non verificato, porta filtrata): va mostrato per intero, non riassunto.
            $this->components->error('Invio fallito: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Mail di prova consegnata al mailer \"{$mailer}\" per {$recipient}.");

        return self::SUCCESS;
    }

    /**
     * Parametri effettivi del mailer, con i segreti mascherati: l'output finisce
     * spesso incollato in chat o in un ticket.
     *
     * @return array<string, string>
     */
    private function settingsFor(string $mailer): array
    {
        $config = config("mail.mailers.{$mailer}");

        $settings = [
            'Mailer' => $mailer,
            'Transport' => (string) ($config['transport'] ?? '—'),
        ];

        $settings += match ($config['transport'] ?? null) {
            'smtp' => [
                'Host' => (string) ($config['host'] ?? '—'),
                'Porta' => (string) ($config['port'] ?? '—'),
                'Scheme' => (string) ($config['scheme'] ?? 'auto'),
                'Timeout' => (string) ($config['timeout'] ?? 'nessuno'),
                'Username' => (string) (($config['username'] ?? null) ?: '<fg=red>vuoto</>'),
                'Password' => $this->mask($config['password'] ?? null),
            ],
            'mailgun' => [
                'Domain' => (string) (config('services.mailgun.domain') ?: '<fg=red>vuoto</>'),
                'Endpoint' => (string) config('services.mailgun.endpoint'),
                'Secret' => $this->mask(config('services.mailgun.secret')),
            ],
            default => [],
        };

        $settings['From'] = sprintf('%s <%s>', config('mail.from.name'), config('mail.from.address'));

        return $settings;
    }

    /**
     * Configurazioni che partono senza errori ma non consegnano nulla di reale.
     *
     * @return list<string>
     */
    private function warningsFor(string $mailer): array
    {
        $transport = config("mail.mailers.{$mailer}.transport");
        $warnings = [];

        if (in_array($transport, ['log', 'array'], true)) {
            $warnings[] = "Il transport \"{$transport}\" non invia nulla: la mail resta in locale. "
                .'Usa --mailer=smtp per provare Mailgun davvero.';
        }

        if ($transport === 'smtp' && blank(config("mail.mailers.{$mailer}.username"))) {
            $warnings[] = 'Nessuna credenziale SMTP: Mailgun rifiuterà l\'autenticazione.';
        }

        if ($transport === 'mailgun' && blank(config('services.mailgun.secret'))) {
            $warnings[] = 'MAILGUN_SECRET non impostata: la chiamata API verrà rifiutata.';
        }

        if (str_contains((string) config('mail.from.address'), 'example.com')) {
            $warnings[] = 'MAIL_FROM_ADDRESS è ancora un indirizzo di esempio: Mailgun accetta solo '
                .'mittenti del dominio verificato.';
        }

        return $warnings;
    }

    private function mask(?string $secret): string
    {
        if (blank($secret)) {
            return '<fg=red>vuoto</>';
        }

        return str_repeat('•', 8).' ('.strlen($secret).' caratteri)';
    }
}
