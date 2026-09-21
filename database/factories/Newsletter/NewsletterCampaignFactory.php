<?php

namespace Database\Factories\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    protected $model = NewsletterCampaign::class;

    public function definition(): array
    {
        return [
            'subject' => ['it' => 'Cinque camminate sul Garda', 'en' => 'Five walks on Lake Garda'],
            'preheader' => ['it' => 'Sentieri all\'ombra e soste pet friendly', 'en' => 'Shaded trails and dog-friendly stops'],
            'body' => ['it' => '<p>L\'autunno è la stagione migliore.</p>', 'en' => '<p>Autumn is the best season.</p>'],
            'audience' => NewsletterCampaign::AUDIENCE_ALL,
            'hourly_rate' => 200,
            'status' => NewsletterCampaign::STATUS_DRAFT,
        ];
    }

    public function sent(int $sent = 100, int $opened = 40): static
    {
        return $this->state(fn () => [
            'status' => NewsletterCampaign::STATUS_SENT,
            'recipients_count' => $sent,
            'sent_count' => $sent,
            'opened_count' => $opened,
            'started_at' => now()->subDays(3),
            'finished_at' => now()->subDays(3)->addHour(),
        ]);
    }
}
