<?php

namespace App\Console\Commands;

use App\Models\Newsletter\NewsletterCampaign;
use App\Services\Newsletter\CampaignSender;
use Illuminate\Console\Command;

/**
 * Riprende una campagna rimasta a metà (worker fermo, coda svuotata, job
 * fallito): riavvia la catena dei lotti sui soli destinatari ancora in coda.
 * Chi ha già ricevuto la mail non la riceve una seconda volta, e chi era a
 * metà spedizione al momento dell'interruzione viene chiuso come fallito
 * invece di essere rispedito.
 */
class NewsletterResumeCommand extends Command
{
    protected $signature = 'newsletter:resume
        {campaign : id della campagna}
        {--force : Riprendi anche se l\'invio sembra ancora in corso}';

    protected $description = 'Riprende l\'invio di una campagna interrotta, senza doppi invii';

    public function handle(CampaignSender $sender): int
    {
        $campaign = NewsletterCampaign::find($this->argument('campaign'));

        if ($campaign === null || $campaign->status !== NewsletterCampaign::STATUS_SENDING) {
            $this->components->error(__('admin-newsletter.command.resume_not_sending'));

            return self::FAILURE;
        }

        // Due catene vive non spediscono doppioni, ma raddoppiano il ritmo.
        if ($sender->looksAlive($campaign) && ! $this->option('force')) {
            $this->components->warn(__('admin-newsletter.command.resume_alive', [
                'time' => $sender->lastActivity($campaign)?->format('H:i'),
            ]));

            return self::FAILURE;
        }

        $queued = $sender->resume($campaign);

        $this->components->info(__('admin-newsletter.command.resume_done', ['count' => $queued]));

        return self::SUCCESS;
    }
}
