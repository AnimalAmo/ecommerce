<?php

namespace App\Jobs\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use App\Services\Newsletter\CampaignSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Fotografa la lista di una campagna appena partita e mette in coda i lotti. */
class BuildCampaignRecipients implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $campaignId)
    {
        $this->afterCommit();
    }

    public function handle(CampaignSender $sender): void
    {
        $campaign = NewsletterCampaign::find($this->campaignId);

        if ($campaign !== null) {
            $sender->buildRecipients($campaign);
        }
    }
}
