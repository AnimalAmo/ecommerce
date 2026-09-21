<?php

namespace App\Jobs\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use App\Services\Newsletter\CampaignSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Un anello della catena di invio: spedisce il prossimo lotto di una campagna
 * e mette in coda il successivo, ritardato quanto serve a tenere il ritmo.
 * Rilanciarlo è sicuro: un passo della catena gira una volta sola, e prende
 * solo righe ancora in coda.
 */
class SendCampaignBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Sotto il retry_after della coda (90 s): oltre, un altro worker riprende
     * il job mentre è ancora in corso. Il lotto più grande (50 mail) ci sta.
     */
    public int $timeout = 80;

    /**
     * @param  string  $chain  la catena di cui fa parte
     * @param  int  $step  il suo posto nella catena: ogni passo gira una volta
     */
    public function __construct(
        public int $campaignId,
        public string $chain = '',
        public int $step = 0,
    ) {
        $this->afterCommit();
    }

    public function handle(CampaignSender $sender): void
    {
        $campaign = NewsletterCampaign::find($this->campaignId);

        if ($campaign !== null) {
            $sender->sendNextBatch($campaign, $this->chain !== '' ? $this->chain : null, $this->step);
        }
    }
}
