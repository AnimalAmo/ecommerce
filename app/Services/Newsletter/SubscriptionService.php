<?php

namespace App\Services\Newsletter;

use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Iscrizione con double opt-in, conferma e disiscrizione.
 *
 * Chi chiama non sa mai se l'indirizzo era già in lista: subscribe() risponde
 * allo stesso modo in ogni caso, e il form pubblico mostra lo stesso
 * messaggio ("controlla la casella") — altrimenti il form diventerebbe un
 * modo per sapere chi è iscritto.
 *
 * `users.newsletter` è vero solo per chi ha confermato: lo leggono il resto
 * del sito e il pannello utenti, e un sì senza conferma non è un'iscrizione.
 */
class SubscriptionService
{
    /** Un secondo invio del form entro questo tempo non manda un'altra mail. */
    private const RESEND_COOLDOWN_MINUTES = 10;

    public function __construct(private NewsletterMailer $mailer) {}

    /**
     * @param  string  $consentText  la frase esatta, nella lingua mostrata, accanto al form o alla casella
     */
    public function subscribe(
        string $email,
        string $locale,
        string $source,
        string $consentText,
        ?string $ip,
        ?string $userAgent,
        ?User $user = null,
    ): NewsletterSubscriber {
        $email = Str::lower(trim($email));
        $locale = in_array($locale, ['it', 'en'], true) ? $locale : 'it';

        $subscriber = NewsletterSubscriber::firstWhere('email', $email)
            ?? new NewsletterSubscriber(['email' => $email, 'token' => NewsletterSubscriber::newToken()]);

        $userId = $user?->getKey() ?? $subscriber->user_id ?? $this->userIdFor($email);

        // Già confermato, o in lista di soppressione: nessuna mail, nessuna
        // modifica alla prova. Al più si aggancia l'account appena creato.
        if ($subscriber->exists && ($subscriber->isConfirmed() || $subscriber->isSuppressed())) {
            if ($subscriber->user_id === null && $userId !== null) {
                $subscriber->forceFill(['user_id' => $userId])->save();
                $this->syncUser($subscriber);
            }

            return $subscriber;
        }

        // Seconda richiesta per un indirizzo appena scritto: nessuna mail e
        // nessuna modifica. Il form è pubblico, e chiunque lo compili con
        // l'indirizzo di un altro riscriverebbe la prova della sua richiesta.
        $recentlySent = $subscriber->exists
            && $subscriber->status === NewsletterSubscriber::STATUS_PENDING
            && $subscriber->confirmation_sent_at?->gt(now()->subMinutes(self::RESEND_COOLDOWN_MINUTES));

        if ($recentlySent) {
            if ($subscriber->user_id === null && $userId !== null) {
                $subscriber->forceFill(['user_id' => $userId])->save();
            }

            return $subscriber;
        }

        // Ritorno dopo una disiscrizione: token nuovo, così i link delle
        // vecchie mail di conferma non riattivano niente, e via la conferma
        // del giro precedente, che non prova più nulla. Chi è ancora in attesa
        // tiene il token: la mail precedente resta buona.
        if ($subscriber->exists && $subscriber->status !== NewsletterSubscriber::STATUS_PENDING) {
            $subscriber->forceFill([
                'token' => NewsletterSubscriber::newToken(),
                'confirmed_at' => null,
                'confirmation_ip' => null,
                'confirmation_user_agent' => null,
            ]);
        }

        // Nuova richiesta (o ritorno dopo una disiscrizione): torna in attesa
        // con la prova di QUESTA richiesta.
        $subscriber->fill([
            'user_id' => $userId,
            'locale' => $locale,
            'status' => NewsletterSubscriber::STATUS_PENDING,
            'source' => $source,
            'legacy' => false,
            'consent_text' => $consentText,
            'consent_ip' => $ip,
            'consent_user_agent' => $userAgent === null ? null : Str::limit($userAgent, 250, ''),
            'requested_at' => now(),
            'confirmation_sent_at' => now(),
            'unsubscribed_at' => null,
        ]);

        $subscriber->save();
        $this->syncUser($subscriber);

        $this->mailer->send($subscriber->email, new NewsletterConfirmationMail($subscriber));

        return $subscriber;
    }

    /**
     * L'iscrizione a cui porta un link di conferma, se il link vale ancora:
     * in attesa e con la mail spedita da meno di confirmation_ttl_days, oppure
     * già confermata (un secondo clic mostra di nuovo l'esito).
     */
    public function findByConfirmationToken(string $token): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::firstWhere('token', $token);

        if ($subscriber === null || $subscriber->isConfirmed()) {
            return $subscriber;
        }

        if ($subscriber->status !== NewsletterSubscriber::STATUS_PENDING || $this->confirmationExpired($subscriber)) {
            return null;
        }

        return $subscriber;
    }

    /**
     * Conferma dalla pagina del link. Null se il link non porta a nessuna
     * iscrizione confermabile (token sconosciuto o scaduto, disiscritto,
     * soppresso). Una seconda conferma non cambia la prova.
     */
    public function confirm(string $token, ?string $ip, ?string $userAgent): ?NewsletterSubscriber
    {
        $subscriber = $this->findByConfirmationToken($token);

        if ($subscriber === null || $subscriber->isConfirmed()) {
            return $subscriber;
        }

        $userAgent = $userAgent === null ? null : Str::limit($userAgent, 250, '');

        $subscriber->forceFill([
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmation_ip' => $ip,
            'confirmation_user_agent' => $userAgent,
            // Vecchia casella: nessun form compilato, il consenso È il clic.
            'consent_text' => $subscriber->consent_text
                ?? __('newsletter.confirm.consent', [], $subscriber->locale),
            'consent_ip' => $subscriber->consent_ip ?? $ip,
            'consent_user_agent' => $subscriber->consent_user_agent ?? $userAgent,
        ])->save();

        $this->syncUser($subscriber);

        return $subscriber;
    }

    /** Disiscrizione immediata. Un indirizzo soppresso resta soppresso. */
    public function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        if ($subscriber->isSuppressed() || $subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            return;
        }

        $subscriber->forceFill([
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ])->save();

        $this->syncUser($subscriber);
    }

    /**
     * Lista di soppressione: rimbalzo definitivo o segnalazione di spam. Non
     * riceverà più nulla, nemmeno una nuova conferma se si riscrive.
     */
    public function suppress(NewsletterSubscriber $subscriber, string $status): void
    {
        if ($subscriber->isSuppressed()) {
            return;
        }

        $subscriber->forceFill([
            'status' => $status,
            'suppressed_at' => now(),
        ])->save();

        $this->syncUser($subscriber);
    }

    /**
     * Travaso della vecchia casella di registrazione: chi ha `users.newsletter`
     * vero senza una riga in lista entra come "da confermare" (legacy, nessuna
     * prova), e il flag torna falso finché non conferma. La migration del
     * modulo lo ha già fatto una volta; qui si raccolgono le registrazioni
     * arrivate con il codice vecchio dopo la migration. Ritorna quanti sono.
     */
    public function importLegacyFlags(): int
    {
        $imported = 0;

        $this->legacyFlagsQuery()
            ->lazyById(200)
            ->each(function (User $user) use (&$imported): void {
                $subscriber = NewsletterSubscriber::firstOrCreate(
                    ['email' => Str::lower(trim($user->email))],
                    [
                        'user_id' => $user->getKey(),
                        'locale' => 'it',
                        'status' => NewsletterSubscriber::STATUS_PENDING,
                        'source' => NewsletterSubscriber::SOURCE_LEGACY,
                        'legacy' => true,
                        'token' => NewsletterSubscriber::newToken(),
                        'requested_at' => $user->created_at,
                    ],
                );

                if ($subscriber->user_id === null) {
                    $subscriber->forceFill(['user_id' => $user->getKey()])->save();
                }

                $imported += $subscriber->wasRecentlyCreated ? 1 : 0;
                $this->syncUser($subscriber);
            });

        return $imported;
    }

    /** Quanti flag della vecchia casella importLegacyFlags() raccoglierebbe. */
    public function legacyFlagsToImport(): int
    {
        return $this->legacyFlagsQuery()->count();
    }

    /** @return Builder<User> */
    private function legacyFlagsQuery(): Builder
    {
        return User::query()
            ->where('newsletter', true)
            ->whereNotIn('id', NewsletterSubscriber::query()->whereNotNull('user_id')->select('user_id'));
    }

    /**
     * Mail di conferma di cortesia ai contatti della vecchia casella mai
     * contattati. Una volta sola: confirmation_sent_at chiude il giro, chi non
     * risponde non viene ricontattato. Ritorna quante ne sono partite.
     */
    public function sendLegacyConfirmations(): int
    {
        $sent = 0;

        NewsletterSubscriber::legacyNeverContacted()
            ->lazyById(200)
            ->each(function (NewsletterSubscriber $subscriber) use (&$sent): void {
                // Marcato prima dell'invio: un secondo lancio concorrente (o un
                // doppio clic nel pannello) non deve produrre una seconda mail.
                $claimed = NewsletterSubscriber::whereKey($subscriber->getKey())
                    ->whereNull('confirmation_sent_at')
                    ->update(['confirmation_sent_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                $this->mailer->send($subscriber->email, new NewsletterConfirmationMail($subscriber->refresh(), courtesy: true));
                $sent++;
            });

        return $sent;
    }

    private function confirmationExpired(NewsletterSubscriber $subscriber): bool
    {
        $days = (int) config('newsletter.confirmation_ttl_days');

        return $subscriber->confirmation_sent_at === null
            || ($days > 0 && $subscriber->confirmation_sent_at->lt(now()->subDays($days)));
    }

    private function userIdFor(string $email): ?int
    {
        $id = User::query()->whereRaw('LOWER(email) = ?', [$email])->value('id');

        return $id === null ? null : (int) $id;
    }

    private function syncUser(NewsletterSubscriber $subscriber): void
    {
        if ($subscriber->user_id === null) {
            return;
        }

        DB::table('users')
            ->where('id', $subscriber->user_id)
            ->update(['newsletter' => $subscriber->isConfirmed()]);
    }
}
