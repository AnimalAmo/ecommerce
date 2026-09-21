<?php

namespace App\Services\Newsletter;

use App\Jobs\Newsletter\BuildCampaignRecipients;
use App\Jobs\Newsletter\SendCampaignBatch;
use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\Exceptions\CampaignNotLaunchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Invio di una campagna: prova, partenza, costruzione della lista, lotti.
 *
 * Il ritmo è una catena di lotti: ogni lotto spedisce al più batchSize() mail
 * e, se resta qualcuno, mette in coda il successivo con un ritardo che tiene
 * il ritmo orario. Con il worker fermo la catena si ferma e basta: niente
 * lotti arretrati che partono tutti insieme alla ripresa.
 *
 * Nessun doppio invio: ogni riga passa queued → sending con un aggiornamento
 * condizionato prima della spedizione, quindi due lotti (una catena ripresa
 * mentre l'altra era viva, un job ritentato) non spediscono mai la stessa
 * riga. Una riga rimasta `sending` dopo un'interruzione non si rispedisce.
 *
 * Nessuna catena doppia: ogni anello (catena, passo) gira una volta sola. Un
 * job ripreso dalla coda mentre era ancora in corso (retry_after scaduto)
 * trova il passo già preso e non fa nulla, invece di spedire un lotto in più
 * e mettere in coda un secondo anello successivo.
 *
 * Errori temporanei di Mailgun (429, 5xx, rete): la riga torna in coda, fino
 * a MAX_ATTEMPTS tentativi, e il lotto si ferma per RETRY_BACKOFF_MINUTES.
 */
class CampaignSender
{
    /**
     * Lotto per "tutti subito": deve chiudersi ben dentro il timeout del job,
     * a sua volta sotto il retry_after della coda (90 secondi).
     */
    public const IMMEDIATE_BATCH = 50;

    /** Tentativi di spedizione per destinatario, di fronte a errori temporanei. */
    public const MAX_ATTEMPTS = 3;

    /** Pausa della catena dopo un errore temporaneo, anche in "tutti subito". */
    public const RETRY_BACKOFF_MINUTES = 5;

    /** Minuti dopo i quali una riga `sending` è considerata interrotta. */
    public const STALE_SENDING_MINUTES = 15;

    public function __construct(private NewsletterMailer $mailer) {}

    /** Quanti iscritti confermati riceverebbero una campagna con quel pubblico. */
    public function audienceCount(string $audience): int
    {
        return NewsletterSubscriber::audience($audience)->count();
    }

    /**
     * Ritmi proposti nell'editor (invii all'ora, 0 = tutti subito), dentro il
     * tetto NEWSLETTER_MAX_PER_HOUR se c'è: allora "tutti subito" diventa il
     * tetto stesso.
     *
     * @return list<int>
     */
    public function rateOptions(): array
    {
        $rates = collect(config('newsletter.rates', [200, 500, 0]))->map(fn ($rate): int => (int) $rate);
        $cap = $this->cap();

        if ($cap > 0) {
            $rates = $rates->filter(fn (int $rate): bool => $rate > 0 && $rate < $cap)->push($cap);
        }

        return $rates->unique()->values()->all();
    }

    /** Il ritmo con cui partirà davvero una campagna: quello scelto, sotto il tetto. 0 = senza limite. */
    public function effectiveRate(int $hourlyRate): int
    {
        $cap = $this->cap();

        if ($cap === 0) {
            return max(0, $hourlyRate);
        }

        return $hourlyRate <= 0 ? $cap : min($hourlyRate, $cap);
    }

    /** Mail per lotto con quel ritmo orario. */
    public function batchSize(int $hourlyRate): int
    {
        $rate = $this->effectiveRate($hourlyRate);

        if ($rate === 0) {
            return self::IMMEDIATE_BATCH;
        }

        return max(1, intdiv($rate * $this->interval(), 60));
    }

    /** Minuti fra un lotto e il successivo: quanto basta a restare sotto il ritmo. */
    public function batchDelayMinutes(int $hourlyRate): int
    {
        $rate = $this->effectiveRate($hourlyRate);

        if ($rate === 0) {
            return 0;
        }

        return max($this->interval(), (int) ceil($this->batchSize($hourlyRate) * 60 / $rate));
    }

    /** Minuti stimati per spedire $count mail a quel ritmo. */
    public function estimatedMinutes(int $count, int $hourlyRate): int
    {
        if ($count === 0) {
            return 0;
        }

        $batches = (int) ceil($count / $this->batchSize($hourlyRate));

        return ($batches - 1) * $this->batchDelayMinutes($hourlyRate);
    }

    /**
     * Invio di prova della versione in una lingua. Non tocca updated_at: la
     * checklist confronta la prova con l'ultima modifica del testo.
     */
    public function sendTest(NewsletterCampaign $campaign, string $email, string $locale): void
    {
        $this->mailer->send($email, new NewsletterCampaignMail($campaign, $locale));

        NewsletterCampaign::withoutTimestamps(fn () => $campaign->forceFill([
            'test_sent_to' => $email,
            'test_sent_at' => now(),
        ])->save());
    }

    /**
     * Perché "Invia a tutti" non può partire, o null se può. I messaggi sono
     * del pannello (solo italiano).
     *
     * In produzione contano anche mailer e coda (in locale e nei test si
     * prova apposta con quelli di default): dal dominio della posta di
     * servizio una segnalazione di spam fermerebbe anche le conferme di
     * prenotazione, e con una coda che esegue i job sul posto la lista
     * partirebbe tutta dentro la richiesta, senza ritmo né tetto orario.
     */
    public function launchBlocker(NewsletterCampaign $campaign): ?string
    {
        return match (true) {
            ! $campaign->isDraft() => __('admin-newsletter.errors.not_draft'),
            ! $campaign->hasVersion('it') => __('admin-newsletter.errors.missing_italian'),
            $this->audienceCount($campaign->audience) === 0 => __('admin-newsletter.errors.empty_audience'),
            default => $this->productionSendBlocker(),
        };
    }

    /**
     * I controlli che valgono per ogni invio in produzione, lancio o ripresa:
     * mailer con un dominio suo e una coda che rispetta il ritmo dei lotti.
     */
    private function productionSendBlocker(): ?string
    {
        return match (true) {
            ! app()->isProduction() => null,
            $this->sharesServiceMailDomain() => __('admin-newsletter.errors.shared_mailer'),
            $this->queueRunsInline() => __('admin-newsletter.errors.inline_queue'),
            default => null,
        };
    }

    /**
     * La newsletter partirebbe dal dominio Mailgun della posta di servizio:
     * NEWSLETTER_MAILER vuoto o uguale al mailer di default, oppure un mailer
     * Mailgun senza dominio suo, che ricade su MAILGUN_DOMAIN (config/mail.php).
     */
    private function sharesServiceMailDomain(): bool
    {
        $mailer = (string) config('newsletter.mailer');

        if ($mailer === '' || $mailer === config('mail.default')) {
            return true;
        }

        if (config("mail.mailers.{$mailer}.transport") !== 'mailgun') {
            return false;
        }

        $serviceDomain = config('services.mailgun.domain');
        $domain = config("mail.mailers.{$mailer}.domain") ?: $serviceDomain;

        return blank($domain) || $domain === $serviceDomain;
    }

    /**
     * Code che eseguono il job subito, nello stesso giro, e ignorano il
     * ritardo con cui queueNextBatch() distanzia i lotti; più la coda null,
     * che i job li butta e lascerebbe la campagna "in invio" per sempre.
     */
    private function queueRunsInline(): bool
    {
        $connection = (string) config('queue.default');
        $driver = config("queue.connections.{$connection}.driver", $connection);

        return in_array($driver, ['sync', 'deferred', 'background', 'null'], true);
    }

    /**
     * "Invia a tutti". Una bozza sola volta: il passaggio draft → sending è
     * condizionato, e un doppio clic non fa partire due liste.
     *
     * @throws CampaignNotLaunchable
     */
    public function launch(NewsletterCampaign $campaign): void
    {
        if (($blocker = $this->launchBlocker($campaign)) !== null) {
            throw new CampaignNotLaunchable($blocker);
        }

        $started = NewsletterCampaign::whereKey($campaign->getKey())
            ->where('status', NewsletterCampaign::STATUS_DRAFT)
            ->update([
                'status' => NewsletterCampaign::STATUS_SENDING,
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        if ($started === 0) {
            throw new CampaignNotLaunchable(__('admin-newsletter.errors.not_draft'));
        }

        $campaign->refresh();

        BuildCampaignRecipients::dispatch($campaign->getKey());
    }

    /**
     * Fotografa la lista al momento della partenza: chi si iscrive dopo non
     * riceve questo numero. insertOrIgnore sulla chiave (campagna, iscritto):
     * rieseguito dopo un crash non duplica nulla.
     */
    public function buildRecipients(NewsletterCampaign $campaign): void
    {
        if ($campaign->status !== NewsletterCampaign::STATUS_SENDING) {
            return;
        }

        // Lista già costruita: un secondo passaggio (job ritentato, ripresa
        // arrivata prima del job) non avvia una seconda catena. Se il primo è
        // morto prima di avviarla, la campagna risulta ferma e si riprende.
        if ($campaign->recipients()->exists()) {
            return;
        }

        $now = now()->toDateTimeString();

        DB::table('newsletter_campaign_recipients')->insertOrIgnoreUsing(
            ['newsletter_campaign_id', 'newsletter_subscriber_id', 'status', 'created_at', 'updated_at'],
            NewsletterSubscriber::audience($campaign->audience)
                ->toBase()
                ->selectRaw('? as newsletter_campaign_id, id as newsletter_subscriber_id, ? as status, ? as created_at, ? as updated_at', [
                    $campaign->getKey(),
                    NewsletterCampaignRecipient::STATUS_QUEUED,
                    $now,
                    $now,
                ]),
        );

        $campaign->forceFill(['recipients_count' => $campaign->recipients()->count()])->save();

        $this->queueNextBatch($campaign);
    }

    /**
     * Un anello della catena: prende i prossimi destinatari ancora in coda,
     * li spedisce, e mette in coda il lotto successivo (o chiude la campagna).
     * `$chain` e `$step` identificano l'anello: senza, nessuna protezione dai
     * doppioni (chiamata diretta).
     */
    public function sendNextBatch(NewsletterCampaign $campaign, ?string $chain = null, int $step = 0): void
    {
        if ($campaign->status !== NewsletterCampaign::STATUS_SENDING) {
            return;
        }

        $runKey = $chain === null ? null : "newsletter:chain:{$chain}:{$step}";

        if ($runKey !== null && ! Cache::add($runKey, true, now()->addDay())) {
            return;
        }

        try {
            $this->runBatch($campaign, $chain, $step);
        } catch (Throwable $exception) {
            // Un'eccezione vera (non di spedizione): il job ritentato deve
            // poter rifare questo anello.
            if ($runKey !== null) {
                Cache::forget($runKey);
            }

            throw $exception;
        }
    }

    private function runBatch(NewsletterCampaign $campaign, ?string $chain, int $step): void
    {
        $recipients = $campaign->recipients()
            ->where('status', NewsletterCampaignRecipient::STATUS_QUEUED)
            ->with('subscriber')
            ->orderBy('id')
            ->limit($this->batchSize($campaign->hourly_rate))
            ->get();

        $retryLater = false;

        foreach ($recipients as $recipient) {
            if ($this->claim($recipient) && $this->sendOne($campaign, $recipient)) {
                // Mailgun non risponde o rallenta: inutile insistere adesso.
                $retryLater = true;

                break;
            }
        }

        if ($campaign->recipients()->where('status', NewsletterCampaignRecipient::STATUS_QUEUED)->exists()) {
            $delay = $this->batchDelayMinutes($campaign->hourly_rate);

            $this->queueNextBatch($campaign, $retryLater ? max($delay, self::RETRY_BACKOFF_MINUTES) : $delay, $chain, $step);

            return;
        }

        $this->finishIfDone($campaign);
    }

    /**
     * Riprende una campagna rimasta a metà (worker fermo, coda svuotata, job
     * fallito): chiude come fallite le righe interrotte a metà spedizione —
     * esito sconosciuto, non si rispedisce — e riavvia la catena sulle righe
     * ancora in coda. Ritorna quante sono.
     *
     * @throws CampaignNotLaunchable in produzione, con mailer o coda inadatti
     */
    public function resume(NewsletterCampaign $campaign): int
    {
        if (($blocker = $this->productionSendBlocker()) !== null) {
            throw new CampaignNotLaunchable($blocker);
        }

        if ($campaign->recipients()->doesntExist()) {
            $this->buildRecipients($campaign);

            return $campaign->recipients()->count();
        }

        $interrupted = $campaign->recipients()
            ->where('status', NewsletterCampaignRecipient::STATUS_SENDING)
            ->where('updated_at', '<', now()->subMinutes(self::STALE_SENDING_MINUTES))
            ->update([
                'status' => NewsletterCampaignRecipient::STATUS_FAILED,
                'error' => 'interrupted',
                'updated_at' => now(),
            ]);

        if ($interrupted > 0) {
            $campaign->increment('failed_count', $interrupted);
        }

        $queued = $campaign->recipients()->where('status', NewsletterCampaignRecipient::STATUS_QUEUED)->count();

        $queued > 0 ? $this->queueNextBatch($campaign) : $this->finishIfDone($campaign);

        return $queued;
    }

    /**
     * L'ultima volta che l'invio ha fatto qualcosa: una catena viva lascia
     * una traccia al più ogni batchDelayMinutes().
     */
    public function lastActivity(NewsletterCampaign $campaign): ?Carbon
    {
        $last = $campaign->recipients()->max('updated_at');

        return $last !== null ? Carbon::parse($last) : $campaign->started_at;
    }

    /** L'invio sembra ancora in corso: riprenderlo raddoppierebbe il ritmo. */
    public function looksAlive(NewsletterCampaign $campaign): bool
    {
        $last = $this->lastActivity($campaign);
        $window = $this->batchDelayMinutes($campaign->hourly_rate) + self::STALE_SENDING_MINUTES;

        return $last !== null && $last->gt(now()->subMinutes($window));
    }

    /** Senza `$chain` nasce una catena nuova (partenza, ripresa). */
    private function queueNextBatch(NewsletterCampaign $campaign, int $delayMinutes = 0, ?string $chain = null, int $step = -1): void
    {
        $job = new SendCampaignBatch($campaign->getKey(), $chain ?? Str::random(20), $chain === null ? 0 : $step + 1);

        if ($delayMinutes > 0) {
            $job->delay(now()->addMinutes($delayMinutes));
        }

        dispatch($job);
    }

    private function claim(NewsletterCampaignRecipient $recipient): bool
    {
        return NewsletterCampaignRecipient::whereKey($recipient->getKey())
            ->where('status', NewsletterCampaignRecipient::STATUS_QUEUED)
            ->update(['status' => NewsletterCampaignRecipient::STATUS_SENDING, 'updated_at' => now()]) === 1;
    }

    /**
     * Spedisce una riga già presa dal lotto. True = errore temporaneo, la
     * riga è tornata in coda e il lotto deve fermarsi.
     */
    private function sendOne(NewsletterCampaign $campaign, NewsletterCampaignRecipient $recipient): bool
    {
        $subscriber = $recipient->subscriber;

        // Disiscritto o soppresso dopo la partenza: non riceve.
        if ($subscriber === null || ! $subscriber->isConfirmed()) {
            $this->mark($recipient, NewsletterCampaignRecipient::STATUS_SKIPPED);

            return false;
        }

        try {
            $sent = $this->mailer->send(
                $subscriber->email,
                new NewsletterCampaignMail($campaign, $subscriber->locale, $subscriber, $recipient),
            );
        } catch (Throwable $exception) {
            $attempts = $recipient->attempts + 1;
            $error = Str::limit($exception->getMessage(), 1000);

            Log::warning('Newsletter non partita', [
                'campaign' => $campaign->getKey(),
                'recipient' => $recipient->getKey(),
                'attempt' => $attempts,
                'error' => $exception->getMessage(),
            ]);

            if ($this->isTemporary($exception) && $attempts < self::MAX_ATTEMPTS) {
                $this->mark($recipient, NewsletterCampaignRecipient::STATUS_QUEUED, ['attempts' => $attempts, 'error' => $error]);

                return true;
            }

            $this->mark($recipient, NewsletterCampaignRecipient::STATUS_FAILED, ['attempts' => $attempts, 'error' => $error]);
            $campaign->increment('failed_count');

            return false;
        }

        $messageId = $sent?->getMessageId();

        $this->mark($recipient, NewsletterCampaignRecipient::STATUS_SENT, [
            'attempts' => $recipient->attempts + 1,
            'error' => null,
            'sent_at' => now(),
            'message_id' => $messageId === null ? null : trim($messageId, '<>'),
        ]);
        $campaign->increment('sent_count');

        return false;
    }

    /**
     * Vale la pena ritentare? Sì per 429 e 5xx dell'API, per i rifiuti
     * temporanei SMTP (4xx) e quando il server non ha risposto affatto. No per
     * il resto: una chiave sbagliata (401) resta sbagliata anche fra un'ora.
     */
    private function isTemporary(Throwable $exception): bool
    {
        if (! $exception instanceof TransportExceptionInterface) {
            return false;
        }

        if ($exception instanceof HttpTransportException) {
            try {
                $status = $exception->getResponse()->getStatusCode();
            } catch (Throwable) {
                return true;
            }

            return $status === 429 || $status >= 500;
        }

        $code = (int) $exception->getCode();

        return $code === 0 || ($code >= 400 && $code < 500);
    }

    /** @param  array<string, mixed>  $attributes */
    private function mark(NewsletterCampaignRecipient $recipient, string $status, array $attributes = []): void
    {
        NewsletterCampaignRecipient::whereKey($recipient->getKey())
            ->update(['status' => $status, 'updated_at' => now()] + $attributes);
    }

    private function finishIfDone(NewsletterCampaign $campaign): void
    {
        $campaign->refresh();

        if ($campaign->status !== NewsletterCampaign::STATUS_SENDING) {
            return;
        }

        $open = $campaign->recipients()
            ->whereIn('status', [NewsletterCampaignRecipient::STATUS_QUEUED, NewsletterCampaignRecipient::STATUS_SENDING])
            ->exists();

        if ($open) {
            return;
        }

        $campaign->forceFill([
            'status' => $campaign->sent_count === 0 && $campaign->failed_count > 0
                ? NewsletterCampaign::STATUS_FAILED
                : NewsletterCampaign::STATUS_SENT,
            'finished_at' => now(),
        ])->save();
    }

    private function cap(): int
    {
        return max(0, (int) config('newsletter.max_per_hour'));
    }

    private function interval(): int
    {
        return max(1, (int) config('newsletter.batch_every_minutes', 5));
    }
}
