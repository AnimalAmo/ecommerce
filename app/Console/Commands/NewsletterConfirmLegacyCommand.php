<?php

namespace App\Console\Commands;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Contatti della vecchia casella di registrazione (users.newsletter senza
 * prova del consenso): entrano in lista come "da confermare" e ricevono una
 * mail di conferma di cortesia, una volta sola. Chi non conferma resta fuori.
 *
 * Idempotente: rilanciato, non riscrive a nessuno. Va lanciato a mano quando
 * la cliente ha deciso (spec §8.4), non dal deploy.
 */
class NewsletterConfirmLegacyCommand extends Command
{
    protected $signature = 'newsletter:confirm-legacy
        {--dry-run : Conta i contatti senza spedire nulla}';

    protected $description = 'Manda la mail di conferma di cortesia ai contatti della vecchia casella newsletter';

    public function handle(SubscriptionService $subscriptions): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $this->components->info(__('admin-newsletter.command.legacy_imported', ['count' => $subscriptions->importLegacyFlags()]));
        }

        $this->components->info(__('admin-newsletter.command.legacy_pending', [
            // A vuoto non si importa: si contano anche i flag ancora da raccogliere.
            'count' => NewsletterSubscriber::legacyNeverContacted()->count() + ($dryRun ? $subscriptions->legacyFlagsToImport() : 0),
        ]));

        if ($dryRun) {
            $this->components->warn(__('admin-newsletter.command.legacy_dry_run'));

            return self::SUCCESS;
        }

        $this->components->info(__('admin-newsletter.command.legacy_sent', ['count' => $subscriptions->sendLegacyConfirmations()]));

        return self::SUCCESS;
    }
}
