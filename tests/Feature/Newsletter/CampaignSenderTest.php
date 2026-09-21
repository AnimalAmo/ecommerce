<?php

namespace Tests\Feature\Newsletter;

use App\Jobs\Newsletter\BuildCampaignRecipients;
use App\Jobs\Newsletter\SendCampaignBatch;
use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\CampaignSender;
use App\Services\Newsletter\Exceptions\CampaignNotLaunchable;
use App\Services\Newsletter\NewsletterMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/**
 * Invio a scaglioni: lista fotografata alla partenza, un lotto dopo l'altro
 * al ritmo scelto, ogni destinatario nella sua lingua, nessun doppio invio
 * dopo un'interruzione.
 */
class CampaignSenderTest extends TestCase
{
    use RefreshDatabase;

    private function sender(): CampaignSender
    {
        return app(CampaignSender::class);
    }

    public function test_a_launch_sends_every_confirmed_subscriber_once_in_their_language(): void
    {
        Mail::fake();
        $italian = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'marta@example.com']);
        $english = NewsletterSubscriber::factory()->confirmed()->english()->create(['email' => 'john@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'attesa@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'via@example.com']);
        NewsletterSubscriber::factory()->bounced()->create(['email' => 'rimbalza@example.com']);
        $campaign = NewsletterCampaign::factory()->create();

        $this->sender()->launch($campaign);

        Mail::assertSent(NewsletterCampaignMail::class, 2);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('marta@example.com')
            && $mail->contentLocale === 'it'
            && $mail->hasSubject('Cinque camminate sul Garda'));
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('john@example.com')
            && $mail->contentLocale === 'en'
            && $mail->hasSubject('Five walks on Lake Garda'));

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(2, $campaign->recipients_count);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertNotNull($campaign->started_at);
        $this->assertNotNull($campaign->finished_at);
        $this->assertSame(2, $campaign->recipients()->where('status', NewsletterCampaignRecipient::STATUS_SENT)->count());
        $this->assertEqualsCanonicalizing([$italian->id, $english->id], $campaign->recipients()->pluck('newsletter_subscriber_id')->all());
    }

    /** Senza versione inglese l'iscritto inglese riceve tutto in italiano, non metà e metà. */
    public function test_a_missing_english_version_falls_back_to_italian_as_a_whole(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->english()->create(['email' => 'john@example.com']);
        $campaign = NewsletterCampaign::factory()->create([
            'subject' => ['it' => 'Solo in italiano', 'en' => 'Only the subject'],
            'body' => ['it' => '<p>Testo italiano</p>'],
        ]);

        $this->sender()->launch($campaign);

        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->contentLocale === 'it'
            && $mail->hasSubject('Solo in italiano'));
    }

    public function test_the_audience_can_be_a_single_language(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'marta@example.com']);
        NewsletterSubscriber::factory()->confirmed()->english()->create(['email' => 'john@example.com']);

        $this->sender()->launch(NewsletterCampaign::factory()->create(['audience' => NewsletterCampaign::AUDIENCE_EN]));

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('john@example.com'));
    }

    /** Un lotto ogni cinque minuti, grande quanto basta a restare sotto il ritmo orario. */
    public function test_batches_follow_each_other_at_the_chosen_rate(): void
    {
        Mail::fake();
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(20)->create();
        $campaign = NewsletterCampaign::factory()->create(['hourly_rate' => 120]);

        $this->sender()->launch($campaign);
        Queue::assertPushed(BuildCampaignRecipients::class);

        $this->sender()->buildRecipients($campaign->fresh());
        Queue::assertPushed(SendCampaignBatch::class, fn (SendCampaignBatch $job) => $job->delay === null);

        // 120 all'ora = 10 ogni 5 minuti.
        $this->sender()->sendNextBatch($campaign->fresh());
        Mail::assertSent(NewsletterCampaignMail::class, 10);
        Queue::assertPushed(SendCampaignBatch::class, fn (SendCampaignBatch $job) => $job->delay !== null
            && (int) round(now()->diffInMinutes($job->delay)) === 5);

        $this->sender()->sendNextBatch($campaign->fresh());
        Mail::assertSent(NewsletterCampaignMail::class, 20);
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->fresh()->status);
    }

    /** Il tetto di configurazione vale anche per "tutti subito". */
    public function test_the_configured_ceiling_caps_every_campaign(): void
    {
        config(['newsletter.max_per_hour' => 60, 'newsletter.batch_every_minutes' => 5]);

        $this->assertSame(60, $this->sender()->effectiveRate(0));
        $this->assertSame(60, $this->sender()->effectiveRate(500));
        $this->assertSame(5, $this->sender()->batchSize(0));
        $this->assertSame(5, $this->sender()->batchDelayMinutes(0));
        $this->assertSame([60], $this->sender()->rateOptions());

        config(['newsletter.max_per_hour' => 300]);
        $this->assertSame([200, 300], $this->sender()->rateOptions());

        config(['newsletter.max_per_hour' => 0]);
        $this->assertSame([200, 500, 0], $this->sender()->rateOptions());
        $this->assertSame(CampaignSender::IMMEDIATE_BATCH, $this->sender()->batchSize(0));
        $this->assertSame(0, $this->sender()->batchDelayMinutes(0));
    }

    /** A 200 all'ora, 612 indirizzi: poco più di tre ore, come dice l'editor. */
    public function test_the_estimate_matches_the_batches(): void
    {
        $this->assertSame(16, $this->sender()->batchSize(200));
        $this->assertSame(190, $this->sender()->estimatedMinutes(612, 200));
        $this->assertSame(0, $this->sender()->estimatedMinutes(0, 200));
    }

    /**
     * Ripresa dopo un'interruzione: chi ha ricevuto non riceve di nuovo, chi
     * era a metà spedizione non viene rispedito (esito sconosciuto), gli
     * altri partono.
     */
    public function test_resuming_never_sends_twice(): void
    {
        Mail::fake();
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(4)->create();
        $campaign = NewsletterCampaign::factory()->create(['hourly_rate' => 0]);
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        [$sent, $inFlight, $queuedA, $queuedB] = $campaign->recipients()->orderBy('id')->get()->all();
        $sent->forceFill(['status' => NewsletterCampaignRecipient::STATUS_SENT, 'sent_at' => now()])->save();
        $inFlight->forceFill(['status' => NewsletterCampaignRecipient::STATUS_SENDING])->save();
        $campaign->forceFill(['sent_count' => 1])->save();

        $this->travel(30)->minutes();
        $this->artisan('newsletter:resume', ['campaign' => $campaign->id])->assertSuccessful();

        $this->sender()->sendNextBatch($campaign->fresh());

        Mail::assertSent(NewsletterCampaignMail::class, 2);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->recipient->is($queuedA));
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->recipient->is($queuedB));

        $this->assertSame(NewsletterCampaignRecipient::STATUS_FAILED, $inFlight->fresh()->status);
        $this->assertSame('interrupted', $inFlight->fresh()->error);

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(3, $campaign->sent_count);
        $this->assertSame(1, $campaign->failed_count);
    }

    /** Una lista mai costruita (crash prima del primo job) si ricostruisce alla ripresa. */
    public function test_resuming_builds_a_list_that_was_never_built(): void
    {
        Mail::fake();
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaign::STATUS_SENDING, 'started_at' => now()->subHour()]);

        $this->artisan('newsletter:resume', ['campaign' => $campaign->id])->assertSuccessful();

        $this->assertSame(2, $campaign->recipients()->count());
        Queue::assertPushed(SendCampaignBatch::class);
    }

    /** Due catene vive non spediscono doppioni, ma raddoppiano il ritmo: serve --force. */
    public function test_resuming_a_campaign_that_looks_alive_needs_force(): void
    {
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());
        Queue::assertPushed(SendCampaignBatch::class, 1);

        $this->artisan('newsletter:resume', ['campaign' => $campaign->id])->assertFailed();
        Queue::assertPushed(SendCampaignBatch::class, 1);

        $this->artisan('newsletter:resume', ['campaign' => $campaign->id, '--force' => true])->assertSuccessful();
        Queue::assertPushed(SendCampaignBatch::class, 2);
    }

    public function test_resuming_needs_a_campaign_being_sent(): void
    {
        $draft = NewsletterCampaign::factory()->create();

        $this->artisan('newsletter:resume', ['campaign' => $draft->id])
            ->expectsOutputToContain(__('admin-newsletter.command.resume_not_sending'))
            ->assertFailed();
    }

    public function test_whoever_unsubscribes_after_the_launch_is_skipped(): void
    {
        Mail::fake();
        Queue::fake();
        $leaving = NewsletterSubscriber::factory()->confirmed()->create();
        NewsletterSubscriber::factory()->confirmed()->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        $leaving->forceFill(['status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED])->save();
        $this->sender()->sendNextBatch($campaign->fresh());

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        $this->assertSame(NewsletterCampaignRecipient::STATUS_SKIPPED, $campaign->recipients()->where('newsletter_subscriber_id', $leaving->id)->value('status'));
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->fresh()->status);
    }

    public function test_a_transport_error_marks_the_recipient_failed_and_the_campaign_goes_on(): void
    {
        $this->mock(NewsletterMailer::class)
            ->shouldReceive('send')
            ->andThrow(new RuntimeException('Mailgun: domain not found'));
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();

        $this->sender()->launch($campaign);

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_FAILED, $campaign->status);
        $this->assertSame(2, $campaign->failed_count);
        $this->assertStringContainsString('domain not found', (string) $campaign->recipients()->value('error'));
    }

    public function test_a_campaign_cannot_start_twice_or_without_italian_or_without_audience(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);

        $this->assertLaunchRefused($campaign->fresh(), __('admin-newsletter.errors.not_draft'));
        $this->assertLaunchRefused(
            NewsletterCampaign::factory()->create(['body' => ['it' => '<p></p>', 'en' => '<p>English</p>']]),
            __('admin-newsletter.errors.missing_italian'),
        );
        $this->assertLaunchRefused(
            NewsletterCampaign::factory()->create(['audience' => NewsletterCampaign::AUDIENCE_EN]),
            __('admin-newsletter.errors.empty_audience'),
        );

        Mail::assertSent(NewsletterCampaignMail::class, 1);
    }

    public function test_a_test_send_does_not_touch_the_last_edit_time(): void
    {
        Mail::fake();
        $campaign = NewsletterCampaign::factory()->create(['updated_at' => now()->subHour()]);
        $edited = $campaign->updated_at;

        $this->sender()->sendTest($campaign, 'silvia@animalamo.it', 'en');

        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('silvia@animalamo.it')
            && $mail->subscriber === null
            && $mail->contentLocale === 'en');
        $campaign->refresh();
        $this->assertSame('silvia@animalamo.it', $campaign->test_sent_to);
        $this->assertNotNull($campaign->test_sent_at);
        $this->assertEquals($edited, $campaign->updated_at);
    }

    private function assertLaunchRefused(NewsletterCampaign $campaign, string $message): void
    {
        try {
            $this->sender()->launch($campaign);
            $this->fail('La campagna è partita.');
        } catch (CampaignNotLaunchable $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
