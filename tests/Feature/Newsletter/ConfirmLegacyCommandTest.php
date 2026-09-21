<?php

namespace Tests\Feature\Newsletter;

use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Contatti della vecchia casella senza prova del consenso: da confermare, una
 * mail di cortesia, una volta sola. Il comando si lancia a mano.
 */
class ConfirmLegacyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_it_sends_one_courtesy_confirmation_to_each_legacy_contact(): void
    {
        $listed = NewsletterSubscriber::factory()->legacy()->create(['email' => 'paolo@example.com']);
        $straggler = User::factory()->create(['email' => 'elena@example.com', 'newsletter' => true]);
        NewsletterSubscriber::factory()->confirmed()->create();

        $this->artisan('newsletter:confirm-legacy')
            ->expectsOutputToContain(__('admin-newsletter.command.legacy_sent', ['count' => 2]))
            ->assertSuccessful();

        Mail::assertQueued(NewsletterConfirmationMail::class, 2);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->courtesy && $mail->hasTo('paolo@example.com'));
        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->courtesy && $mail->hasTo('elena@example.com'));

        $this->assertNotNull($listed->fresh()->confirmation_sent_at);
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $listed->fresh()->status);
        $this->assertFalse($straggler->fresh()->newsletter);
    }

    public function test_running_it_again_writes_to_nobody(): void
    {
        NewsletterSubscriber::factory()->legacy()->count(3)->create();

        $this->artisan('newsletter:confirm-legacy')->assertSuccessful();
        $this->artisan('newsletter:confirm-legacy')
            ->expectsOutputToContain(__('admin-newsletter.command.legacy_sent', ['count' => 0]))
            ->assertSuccessful();

        Mail::assertQueued(NewsletterConfirmationMail::class, 3);
    }

    public function test_dry_run_counts_without_sending_or_importing(): void
    {
        NewsletterSubscriber::factory()->legacy()->create();
        User::factory()->create(['newsletter' => true]);

        $this->artisan('newsletter:confirm-legacy', ['--dry-run' => true])
            ->expectsOutputToContain(__('admin-newsletter.command.legacy_pending', ['count' => 2]))
            ->assertSuccessful();

        Mail::assertNothingOutgoing();
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    /** Chi conferma dalla mail di cortesia entra in lista, con il clic come prova. */
    public function test_a_legacy_contact_who_confirms_joins_the_list(): void
    {
        $user = User::factory()->create(['newsletter' => true]);

        $this->artisan('newsletter:confirm-legacy')->assertSuccessful();

        $subscriber = NewsletterSubscriber::sole();
        app(SubscriptionService::class)->confirm($subscriber->token, '203.0.113.9', 'Firefox');

        $this->assertTrue($subscriber->fresh()->isConfirmed());
        $this->assertSame(__('newsletter.confirm.consent'), $subscriber->fresh()->consent_text);
        $this->assertTrue($user->fresh()->newsletter);
    }
}
