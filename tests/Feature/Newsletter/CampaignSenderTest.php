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
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\HttpTransportException;
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

    /**
     * Un job ripreso dalla coda mentre girava ancora (retry_after scaduto) non
     * spedisce un lotto in più né avvia un secondo anello: ogni passo della
     * catena gira una volta.
     */
    public function test_each_step_of_the_chain_runs_once(): void
    {
        Mail::fake();
        Queue::fake();
        config(['newsletter.batch_every_minutes' => 5]);
        NewsletterSubscriber::factory()->confirmed()->count(3)->create();
        // 12 all'ora = una mail ogni 5 minuti.
        $campaign = NewsletterCampaign::factory()->create(['hourly_rate' => 12]);
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        $this->sender()->sendNextBatch($campaign->fresh(), 'catena', 4);
        $this->sender()->sendNextBatch($campaign->fresh(), 'catena', 4);

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Queue::assertPushed(SendCampaignBatch::class, fn (SendCampaignBatch $job) => $job->chain === 'catena' && $job->step === 5);
        Queue::assertPushed(SendCampaignBatch::class, 2);

        $this->sender()->sendNextBatch($campaign->fresh(), 'catena', 5);
        Mail::assertSent(NewsletterCampaignMail::class, 2);
    }

    public function test_building_the_list_twice_starts_a_single_chain(): void
    {
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);

        $this->sender()->buildRecipients($campaign->fresh());
        $this->sender()->buildRecipients($campaign->fresh());

        Queue::assertPushed(SendCampaignBatch::class, 1);
        $this->assertSame(2, $campaign->recipients()->count());
    }

    /** Mailgun rallenta o non risponde: la riga torna in coda e la catena si prende una pausa. */
    public function test_a_temporary_mailgun_error_puts_the_recipient_back_in_the_queue(): void
    {
        Queue::fake();
        $calls = 0;
        $this->mock(NewsletterMailer::class)->shouldReceive('send')->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) {
                throw $this->mailgunError(503);
            }

            return null;
        });
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create(['hourly_rate' => 0]);
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        $this->sender()->sendNextBatch($campaign->fresh());

        $first = $campaign->recipients()->orderBy('id')->first();
        $this->assertSame(NewsletterCampaignRecipient::STATUS_QUEUED, $first->status);
        $this->assertSame(1, $first->attempts);
        $this->assertSame(1, $calls);
        Queue::assertPushed(SendCampaignBatch::class, fn (SendCampaignBatch $job) => $job->delay !== null
            && (int) round(now()->diffInMinutes($job->delay)) === CampaignSender::RETRY_BACKOFF_MINUTES);

        $this->sender()->sendNextBatch($campaign->fresh());

        $this->assertSame(NewsletterCampaignRecipient::STATUS_SENT, $first->fresh()->status);
        $this->assertNull($first->fresh()->error);
        $campaign->refresh();
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
    }

    public function test_after_the_last_attempt_the_recipient_fails(): void
    {
        Queue::fake();
        $this->mock(NewsletterMailer::class)->shouldReceive('send')->andThrow($this->mailgunError(429));
        NewsletterSubscriber::factory()->confirmed()->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        foreach (range(1, CampaignSender::MAX_ATTEMPTS) as $attempt) {
            $this->sender()->sendNextBatch($campaign->fresh());
        }

        $recipient = $campaign->recipients()->sole();
        $this->assertSame(NewsletterCampaignRecipient::STATUS_FAILED, $recipient->status);
        $this->assertSame(CampaignSender::MAX_ATTEMPTS, $recipient->attempts);
        $this->assertSame(NewsletterCampaign::STATUS_FAILED, $campaign->fresh()->status);
    }

    /** Una chiave sbagliata resta sbagliata: niente tentativi a vuoto per ore. */
    public function test_a_permanent_api_error_is_not_retried(): void
    {
        Queue::fake();
        $this->mock(NewsletterMailer::class)->shouldReceive('send')->andThrow($this->mailgunError(401));
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());

        $this->sender()->sendNextBatch($campaign->fresh());

        $campaign->refresh();
        $this->assertSame(2, $campaign->failed_count);
        $this->assertSame(NewsletterCampaign::STATUS_FAILED, $campaign->status);
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

    /**
     * Mailgun tiene una lista di soppressione per dominio: dal dominio della
     * posta di servizio, chi segnala come spam un numero della newsletter non
     * riceverebbe più nemmeno le conferme di prenotazione.
     */
    public function test_in_production_the_newsletter_cannot_leave_from_the_service_mail_domain(): void
    {
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->create();
        $this->app['env'] = 'production';
        config([
            'mail.default' => 'mailgun',
            'services.mailgun.domain' => 'mg.animalamo.it',
            'queue.default' => 'database',
        ]);

        config(['newsletter.mailer' => null]);
        $this->assertLaunchRefused(NewsletterCampaign::factory()->create(), __('admin-newsletter.errors.shared_mailer'));

        config(['newsletter.mailer' => 'mailgun']);
        $this->assertLaunchRefused(NewsletterCampaign::factory()->create(), __('admin-newsletter.errors.shared_mailer'));

        // mailgun-newsletter senza MAILGUN_NEWSLETTER_DOMAIN ricade su MAILGUN_DOMAIN (config/mail.php).
        config(['newsletter.mailer' => 'mailgun-newsletter', 'mail.mailers.mailgun-newsletter.domain' => 'mg.animalamo.it']);
        $this->assertLaunchRefused(NewsletterCampaign::factory()->create(), __('admin-newsletter.errors.shared_mailer'));

        config(['mail.mailers.mailgun-newsletter.domain' => 'news.animalamo.it']);
        $campaign = NewsletterCampaign::factory()->create();
        $this->assertNull($this->sender()->launchBlocker($campaign));
        $this->sender()->launch($campaign);
        Queue::assertPushed(BuildCampaignRecipients::class, 1);
    }

    /** Una coda che esegue i job sul posto ignora i ritardi: la lista partirebbe tutta dentro la richiesta. */
    public function test_in_production_the_newsletter_needs_a_queue_that_honours_the_pace(): void
    {
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->create();
        $this->app['env'] = 'production';
        config([
            'newsletter.mailer' => 'mailgun-newsletter',
            'mail.mailers.mailgun-newsletter.domain' => 'news.animalamo.it',
            'services.mailgun.domain' => 'mg.animalamo.it',
        ]);

        foreach (['sync', 'deferred'] as $connection) {
            config(['queue.default' => $connection]);
            $this->assertLaunchRefused(NewsletterCampaign::factory()->create(), __('admin-newsletter.errors.inline_queue'));
        }

        config(['queue.default' => 'database']);
        $this->assertNull($this->sender()->launchBlocker(NewsletterCampaign::factory()->create()));
        Queue::assertNothingPushed();
    }

    /** Il driver null butta i job: la campagna resterebbe "in invio" per sempre. */
    public function test_in_production_a_null_queue_blocks_the_launch(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create();
        $this->productionWithDedicatedMailer();
        config(['queue.default' => 'null']);

        $this->assertLaunchRefused(NewsletterCampaign::factory()->create(), __('admin-newsletter.errors.inline_queue'));
    }

    /**
     * "Riprendi l'invio" rimette in coda i lotti come il lancio: con una coda
     * che li esegue sul posto la lista partirebbe tutta dentro la richiesta.
     */
    public function test_in_production_resuming_needs_the_same_queue_as_launching(): void
    {
        Queue::fake();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->sender()->launch($campaign);
        $this->sender()->buildRecipients($campaign->fresh());
        $this->travel(2)->hours();

        $this->productionWithDedicatedMailer();
        config(['queue.default' => 'sync']);

        $this->artisan('newsletter:resume', ['campaign' => $campaign->id, '--force' => true])
            ->expectsOutputToContain(__('admin-newsletter.errors.inline_queue'))
            ->assertFailed();
        Queue::assertPushed(SendCampaignBatch::class, 1);
    }

    private function productionWithDedicatedMailer(): void
    {
        $this->app['env'] = 'production';
        config([
            'newsletter.mailer' => 'mailgun-newsletter',
            'mail.mailers.mailgun-newsletter.domain' => 'news.animalamo.it',
            'services.mailgun.domain' => 'mg.animalamo.it',
        ]);
    }

    /** In locale e nei test si prova con il mailer di default e la coda sync. */
    public function test_outside_production_the_mailer_and_queue_are_not_checked(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create();
        $this->app['env'] = 'local';
        config(['newsletter.mailer' => null, 'queue.default' => 'sync']);

        $this->assertNull($this->sender()->launchBlocker(NewsletterCampaign::factory()->create()));
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

    /** L'errore che il transport API di Mailgun lancia per una risposta HTTP. */
    private function mailgunError(int $status): HttpTransportException
    {
        $response = (new MockHttpClient(new MockResponse('{"message":"errore"}', ['http_code' => $status])))
            ->request('POST', 'https://api.eu.mailgun.net/v3/mg.animalamo.it/messages');

        return new HttpTransportException("Unable to send an email (code {$status}).", $response);
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
