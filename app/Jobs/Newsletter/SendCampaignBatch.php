<?php

namespace App\Jobs\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use App\Services\Newsletter\CampaignSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Un anello della catena di invio: spedisce il prossimo lotto di una campagna
 * e mette in coda il successivo, ritardato quanto serve a tenere il ritmo.
 * Rilanciarlo è sicuro: prende solo righe ancora in coda, una volta sola.
 */
class SendCampaignBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $campaignId)
    {
        $this->afterCommit();
    }

    public function handle(CampaignSender $sender): void
    {
        $campaign = NewsletterCampaign::find($this->campaignId);

        if ($campaign !== null) {
            $sender->sendNextBatch($campaign);
        }
    }
}
