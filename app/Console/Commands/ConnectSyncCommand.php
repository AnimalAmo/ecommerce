<?php

namespace App\Console\Commands;

use App\Models\Partner\PartnerProfile;
use App\Services\Payment\StripeConnectService;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;

/**
 * Rilegge da Stripe lo stato di ogni account connesso conosciuto.
 *
 * Lo specchio su `partner_profiles` si aggiorna con l'evento `account.updated`.
 * Quando quell'evento non arriva — endpoint Connect non ancora creato, segreto
 * sbagliato, endpoint disabilitato da Stripe dopo troppe consegne fallite — un
 * partner approvato continua a risultare non collegato e non vende. Questo
 * comando è il riallineamento manuale, da lanciare dopo aver sistemato i
 * webhook: un account che fallisce non ferma gli altri. Passa da
 * syncAccountState, quindi un partner che diventa pagabile qui mette in coda
 * anche la pubblicazione dei suoi servizi in attesa (P4).
 *
 * In coda stampa l'elenco dei profili il cui specchio è davvero cambiato, coi
 * flag Stripe prima e dopo: serve a chiudere il ticket da cui il lancio nasce
 * («il partner X non riesce a vendere») senza andare a leggere il database.
 */
class ConnectSyncCommand extends Command
{
    protected $signature = 'animalamo:connect-sync';

    protected $description = 'Rilegge da Stripe lo stato degli account connessi dei partner';

    public function handle(StripeConnectService $connect): int
    {
        $synced = 0;
        $failed = 0;
        /** @var list<array{0: string, 1: string, 2: string, 3: string}> $changed */
        $changed = [];

        PartnerProfile::query()
            ->whereNotNull('stripe_account_id')
            ->with('user')
            ->each(function (PartnerProfile $profile) use ($connect, &$synced, &$failed, &$changed): void {
                $before = $this->stripeFlags($profile);

                try {
                    $connect->syncAccountState((string) $profile->stripe_account_id);
                    $synced++;
                } catch (ApiErrorException $exception) {
                    $failed++;
                    $this->components->warn("{$profile->stripe_account_id}: {$exception->getMessage()}");

                    return;
                }

                // `syncAccountState()` scrive su una SUA istanza del profilo,
                // ritrovata per stripe_account_id: questa qui è rimasta la
                // fotografia di prima, ed è esattamente ciò che serve per il
                // confronto. Il refresh rilegge lo stato appena scritto.
                $after = $this->stripeFlags($profile->refresh());

                if ($after === $before) {
                    return;
                }

                $changed[] = [
                    $profile->user?->email ?? "utente #{$profile->user_id}",
                    (string) $profile->stripe_account_id,
                    $this->transition($before['charges'], $after['charges']),
                    $this->transition($before['payouts'], $after['payouts']),
                ];
            });

        $this->components->info("Account riallineati: {$synced}".($failed > 0 ? ", falliti: {$failed}." : '.'));

        $this->reportChanged($changed);

        return self::SUCCESS;
    }

    /**
     * Lo specchio Stripe ridotto ai due flag che decidono se il partner vende
     * (`canSell`) e se viene pagato (`canBePaid`).
     *
     * `stripe_requirements_due` resta volutamente fuori dal confronto: Stripe lo
     * rimescola a ogni verifica e l'elenco finirebbe pieno di righe in cui per
     * il partner non è cambiato niente, che è il rumore che rende un elenco
     * inutile quanto un conteggio.
     *
     * @return array{charges: bool, payouts: bool}
     */
    private function stripeFlags(PartnerProfile $profile): array
    {
        return [
            'charges' => (bool) $profile->stripe_charges_enabled,
            'payouts' => (bool) $profile->stripe_payouts_enabled,
        ];
    }

    /** «no → sì» quando il flag si muove, il solo valore quando è fermo. */
    private function transition(bool $before, bool $after): string
    {
        return $before === $after
            ? $this->flagLabel($after)
            : $this->flagLabel($before).' → '.$this->flagLabel($after);
    }

    private function flagLabel(bool $value): string
    {
        return $value ? 'sì' : 'no';
    }

    /**
     * Il conteggio da solo non risponde alla domanda per cui si lancia il
     * comando: «è servito?». Un giro che rilegge quaranta account senza
     * cambiare nulla e uno che sblocca il partner che ha aperto il ticket
     * stampavano la stessa riga, e per sapere chi era tornato pagabile bisognava
     * andare a guardare a mano in `partner_profiles`.
     *
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $changed
     */
    private function reportChanged(array $changed): void
    {
        if ($changed === []) {
            $this->components->info('Nessuno specchio Stripe modificato: i profili erano già allineati.');

            return;
        }

        $this->components->warn('Profili cambiati da questo riallineamento:');
        $this->table(['partner', 'account', 'incassi', 'bonifici'], $changed);
    }
}
