<?php

namespace App\Console\Commands;

use App\Models\MailDelivery;
use Illuminate\Console\Command;

/**
 * Sostituisce i log Mailgun (che scadono) e l'area admin (che non esiste
 * ancora): risponde alla domanda della cliente "a chi non è arrivata la mail?"
 * leggendo mail_deliveries, che il webhook tiene aggiornata.
 */
class MailDeliveriesCommand extends Command
{
    protected $signature = 'mail:deliveries
                            {--failed : Solo le consegne fallite o segnalate come spam}
                            {--days=30 : Finestra in giorni}
                            {--recipient= : Filtra per destinatario}';

    protected $description = 'Mostra l\'esito reale delle mail in uscita registrato dai webhook Mailgun';

    public function handle(): int
    {
        // Il controllo va PRIMA della tabella e non dentro il ramo "nessuna riga":
        // il listener apre una riga `sent` per ogni mail che parte anche a webhook
        // spento, quindi dopo il primo invio la tabella non è più vuota e un
        // avviso legato al vuoto non comparirebbe mai più. Si leggerebbe un elenco
        // ordinato con "Non consegnate: 0" mentre in realtà nessun esito è mai
        // arrivato: una rassicurazione falsa, peggio del silenzio.
        $blind = blank(config('services.mailgun.webhook_signing_key'));

        if ($blind) {
            $this->components->error(
                'MAILGUN_WEBHOOK_SIGNING_KEY non è configurata: nessun evento di consegna '
                .'può arrivare. Lo stato "sent" qui sotto significa solo "partita", non "consegnata".'
            );
        }

        $query = MailDelivery::query()
            ->where('created_at', '>=', now()->subDays((int) $this->option('days')))
            ->when($this->option('failed'), fn ($q) => $q->whereIn('status', [
                MailDelivery::STATUS_FAILED,
                MailDelivery::STATUS_COMPLAINED,
            ]))
            ->when($this->option('recipient'), fn ($q, $recipient) => $q->where('recipient', 'like', "%{$recipient}%"))
            ->latest();

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->components->info('Nessuna consegna registrata con questi filtri.');

            return $blind ? self::FAILURE : self::SUCCESS;
        }

        $this->table(
            ['Data', 'Destinatario', 'Mail', 'Stato', 'Motivo'],
            $rows->map(fn (MailDelivery $d) => [
                $d->created_at?->format('d/m H:i'),
                $d->recipient,
                class_basename((string) $d->mailable) ?: '—',
                $d->failed() ? "<fg=red>{$d->status}</>" : $d->status,
                str((string) $d->reason)->limit(50)->value() ?: '—',
            ])->all(),
        );

        $failed = $rows->filter(fn (MailDelivery $d) => $d->failed())->count();

        $this->components->twoColumnDetail('Totale', (string) $rows->count());
        $this->components->twoColumnDetail('Non consegnate', $failed > 0 ? "<fg=red>{$failed}</>" : '0');
        $this->components->twoColumnDetail(
            '<fg=gray>Legenda</>',
            '<fg=gray>sent = partita, esito ignoto · delivered = accettata dal server del destinatario</>'
        );

        // Uscita diversa da 0 a webhook spento: così un deploy senza signing key
        // non passa inosservato a chi lancia il comando da uno script.
        return $blind ? self::FAILURE : self::SUCCESS;
    }
}
