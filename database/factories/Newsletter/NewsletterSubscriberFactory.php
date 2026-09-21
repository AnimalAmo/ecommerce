<?php

namespace Database\Factories\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'locale' => 'it',
            'status' => NewsletterSubscriber::STATUS_PENDING,
            'source' => NewsletterSubscriber::SOURCE_FOOTER,
            'legacy' => false,
            'token' => NewsletterSubscriber::newToken(),
            'consent_text' => 'Iscrivendoti accetti di ricevere la newsletter di AnimalAmo.',
            'consent_ip' => '203.0.113.10',
            'consent_user_agent' => 'Mozilla/5.0 (test)',
            'requested_at' => now()->subDay(),
            'confirmation_sent_at' => now()->subDay(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'confirmed_at' => now()->subHours(20),
            'confirmation_ip' => '203.0.113.10',
            'confirmation_user_agent' => 'Mozilla/5.0 (test)',
        ]);
    }

    public function english(): static
    {
        return $this->state(fn () => ['locale' => 'en']);
    }

    public function unsubscribed(): static
    {
        return $this->confirmed()->state(fn () => [
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now()->subHour(),
        ]);
    }

    public function bounced(): static
    {
        return $this->confirmed()->state(fn () => [
            'status' => NewsletterSubscriber::STATUS_BOUNCED,
            'suppressed_at' => now()->subHour(),
        ]);
    }

    /** Vecchia casella di registrazione: nessuna prova, mai contattato. */
    public function legacy(): static
    {
        return $this->state(fn () => [
            'source' => NewsletterSubscriber::SOURCE_LEGACY,
            'legacy' => true,
            'consent_text' => null,
            'consent_ip' => null,
            'consent_user_agent' => null,
            'confirmation_sent_at' => null,
        ]);
    }
}
