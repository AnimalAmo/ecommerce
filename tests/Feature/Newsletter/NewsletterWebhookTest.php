<?php

namespace Tests\Feature\Newsletter;

use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\CampaignSender;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Il webhook Mailgun pulisce la lista da solo: un indirizzo che rimbalza o
 * segnala spam non riceve più nulla. Consegne e aperture delle campagne
 * alimentano i numeri del pannello.
 */
class NewsletterWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'signing-key-di-test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mailgun.webhook_signing_key' => self::KEY]);
        Http::preventStrayRequests();
    }

    /** @param  array<string, mixed>  $eventData */
    private function postEvent(array $eventData, ?string $key = null): TestResponse
    {
        $timestamp = (string) time();
        $token = 'token-'.md5((string) mt_rand());

        return $this->postJson(route('webhooks.mailgun'), [
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => hash_hmac('sha256', $timestamp.$token, $key ?? self::KEY),
            ],
            'event-data' => $eventData,
        ]);
    }

    public function test_a_permanent_bounce_suppresses_the_address_and_no_mail_follows(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'nomx@example.com', 'newsletter' => true]);
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'nomx@example.com', 'user_id' => $user->id]);

        $this->postEvent([
            'event' => 'failed',
            'severity' => 'permanent',
            'reason' => 'bounce',
            'recipient' => 'NoMX@example.com',
            'message' => ['headers' => ['message-id' => 'abc@mg.animalamo.it']],
        ])->assertOk()->assertJson(['status' => 'ok']);

        $subscriber->refresh();
        $this->assertSame(NewsletterSubscriber::STATUS_BOUNCED, $subscriber->status);
        $this->assertNotNull($subscriber->suppressed_at);
        $this->assertFalse($user->fresh()->newsletter);

        // Né la prossima campagna né una nuova iscrizione gli scrivono.
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'ok@example.com']);
        app(CampaignSender::class)->launch(NewsletterCampaign::factory()->create());
        app(SubscriptionService::class)->subscribe('nomx@example.com', 'it', NewsletterSubscriber::SOURCE_FOOTER, 'Frase', null, null);

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('ok@example.com'));
        Mail::assertNotQueued(NewsletterConfirmationMail::class);
    }

    public function test_a_spam_complaint_suppresses_the_address(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'spam@example.com']);

        $this->postEvent(['event' => 'complained', 'recipient' => 'spam@example.com'])->assertOk();

        $this->assertSame(NewsletterSubscriber::STATUS_COMPLAINED, $subscriber->fresh()->status);
    }

    /** Un rinvio temporaneo (casella piena) si ritenta: non è un rimbalzo. */
    public function test_a_temporary_failure_changes_nothing(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'piena@example.com']);

        $this->postEvent(['event' => 'failed', 'severity' => 'temporary', 'recipient' => 'piena@example.com'])->assertOk();

        $this->assertTrue($subscriber->fresh()->isConfirmed());
    }

    /** Scarto per lista di Mailgun: il motivo dice quale lista. */
    public function test_a_drop_for_mailgun_unsubscribe_list_is_an_unsubscription_not_a_bounce(): void
    {
        $unsubscribed = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'via@example.com']);
        $complained = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'spam@example.com']);

        $this->postEvent(['event' => 'failed', 'severity' => 'permanent', 'reason' => 'suppress-unsubscribe', 'recipient' => 'via@example.com'])->assertOk();
        $this->postEvent(['event' => 'failed', 'severity' => 'permanent', 'reason' => 'suppress-complaint', 'recipient' => 'spam@example.com'])->assertOk();

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $unsubscribed->fresh()->status);
        $this->assertSame(NewsletterSubscriber::STATUS_COMPLAINED, $complained->fresh()->status);
    }

    /** Consegne e aperture contano una volta per destinatario, anche se l'evento si ripete. */
    public function test_deliveries_and_opens_are_counted_once_per_recipient(): void
    {
        [$campaign, $recipient] = $this->sentRecipient('marta@example.com');
        $event = fn (string $type) => [
            'event' => $type,
            'recipient' => 'marta@example.com',
            'timestamp' => now()->timestamp,
            'user-variables' => ['newsletter_campaign_id' => (string) $campaign->id, 'newsletter_recipient_id' => (string) $recipient->id],
            'message' => ['headers' => ['message-id' => 'campagna-1@mg.animalamo.it']],
        ];

        $this->postEvent($event('delivered'))->assertOk();
        $this->postEvent($event('opened'))->assertOk();
        $this->postEvent($event('opened'))->assertOk();

        $campaign->refresh();
        $this->assertSame(1, $campaign->delivered_count);
        $this->assertSame(1, $campaign->opened_count);
        $this->assertNotNull($recipient->fresh()->delivered_at);
        $this->assertNotNull($recipient->fresh()->opened_at);
    }

    /** Senza variabili (invio SMTP senza header) il message-id salvato all'invio basta. */
    public function test_an_open_is_matched_by_message_id_when_variables_are_missing(): void
    {
        [$campaign] = $this->sentRecipient('marta@example.com');

        $this->postEvent([
            'event' => 'opened',
            'recipient' => 'marta@example.com',
            'message' => ['headers' => ['message-id' => 'campagna-1@mg.animalamo.it']],
        ])->assertOk();

        $this->assertSame(1, $campaign->fresh()->opened_count);
    }

    /** La variabile viene dal contenuto della mail: deve combaciare con l'indirizzo dell'evento. */
    public function test_a_recipient_variable_for_another_address_is_not_trusted(): void
    {
        [$campaign, $recipient] = $this->sentRecipient('marta@example.com');

        $this->postEvent([
            'event' => 'opened',
            'recipient' => 'altro@example.com',
            'user-variables' => ['newsletter_recipient_id' => (string) $recipient->id],
        ])->assertOk()->assertJson(['status' => 'ignored']);

        $this->assertSame(0, $campaign->fresh()->opened_count);
    }

    public function test_an_invalid_signature_changes_nothing(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'spam@example.com']);

        $this->postEvent(['event' => 'complained', 'recipient' => 'spam@example.com'], key: 'chiave-sbagliata')->assertForbidden();

        $this->assertTrue($subscriber->fresh()->isConfirmed());
    }

    /** @return array{0: NewsletterCampaign, 1: NewsletterCampaignRecipient} */
    private function sentRecipient(string $email): array
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => $email]);
        $campaign = NewsletterCampaign::factory()->sent(1, 0)->create();
        $recipient = NewsletterCampaignRecipient::create([
            'newsletter_campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
            'status' => NewsletterCampaignRecipient::STATUS_SENT,
            'message_id' => 'campagna-1@mg.animalamo.it',
            'sent_at' => now(),
        ]);

        return [$campaign, $recipient];
    }
}
