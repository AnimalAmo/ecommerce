<?php

namespace Tests\Feature\Newsletter;

use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->service = app(SubscriptionService::class);
    }

    private function subscribe(string $email, string $locale = 'it', string $consent = 'Frase del consenso'): NewsletterSubscriber
    {
        return $this->service->subscribe($email, $locale, NewsletterSubscriber::SOURCE_FOOTER, $consent, '198.51.100.7', 'Firefox/130');
    }

    public function test_a_new_address_becomes_pending_with_the_proof_and_gets_the_confirmation_mail(): void
    {
        $subscriber = $this->subscribe('  Luca.Ferrari@Example.com ', 'en', 'By subscribing I agree');

        $this->assertSame('luca.ferrari@example.com', $subscriber->email);
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->status);
        $this->assertSame('en', $subscriber->locale);
        $this->assertSame('By subscribing I agree', $subscriber->consent_text);
        $this->assertSame('198.51.100.7', $subscriber->consent_ip);
        $this->assertSame('Firefox/130', $subscriber->consent_user_agent);
        $this->assertNotNull($subscriber->requested_at);
        $this->assertNotNull($subscriber->confirmation_sent_at);
        $this->assertSame(64, strlen($subscriber->token));

        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->hasTo('luca.ferrari@example.com')
            && $mail->subscriber->is($subscriber)
            && ! $mail->courtesy
            && $mail->locale === 'en');
    }

    /** Stessa risposta per chi è già in lista: nessuna mail, prova intatta. */
    public function test_an_already_confirmed_address_is_left_untouched(): void
    {
        $existing = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'luca@example.com']);

        $subscriber = $this->subscribe('luca@example.com', consent: 'Altro testo');

        $this->assertTrue($subscriber->is($existing));
        $this->assertSame(NewsletterSubscriber::STATUS_CONFIRMED, $subscriber->fresh()->status);
        $this->assertSame($existing->consent_text, $subscriber->fresh()->consent_text);
        Mail::assertNothingOutgoing();
    }

    public function test_an_unsubscribed_address_can_subscribe_again(): void
    {
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'luca@example.com']);

        $subscriber = $this->subscribe('luca@example.com');

        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->status);
        $this->assertNull($subscriber->unsubscribed_at);
        Mail::assertQueued(NewsletterConfirmationMail::class, 1);
    }

    public function test_a_suppressed_address_never_gets_mail_again(): void
    {
        NewsletterSubscriber::factory()->bounced()->create(['email' => 'nomx@example.com']);
        NewsletterSubscriber::factory()->confirmed()->create([
            'email' => 'spam@example.com',
            'status' => NewsletterSubscriber::STATUS_COMPLAINED,
            'suppressed_at' => now(),
        ]);

        $this->assertSame(NewsletterSubscriber::STATUS_BOUNCED, $this->subscribe('nomx@example.com')->status);
        $this->assertSame(NewsletterSubscriber::STATUS_COMPLAINED, $this->subscribe('spam@example.com')->status);
        Mail::assertNothingOutgoing();
    }

    /** Il form non diventa un modo per riempire di conferme la casella di qualcun altro. */
    public function test_a_second_request_within_minutes_does_not_send_a_second_mail(): void
    {
        $this->subscribe('luca@example.com');
        // Qualcun altro scrive lo stesso indirizzo: la prova della prima
        // richiesta resta com'era.
        $this->service->subscribe('luca@example.com', 'en', NewsletterSubscriber::SOURCE_REGISTRATION, 'Altra frase', '192.0.2.66', 'Bot/1');

        Mail::assertQueued(NewsletterConfirmationMail::class, 1);
        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('Frase del consenso', $subscriber->consent_text);
        $this->assertSame('198.51.100.7', $subscriber->consent_ip);
        $this->assertSame('it', $subscriber->locale);
        $this->assertSame(NewsletterSubscriber::SOURCE_FOOTER, $subscriber->source);

        $this->travel(11)->minutes();
        $this->subscribe('luca@example.com');

        Mail::assertQueued(NewsletterConfirmationMail::class, 2);
    }

    public function test_confirming_records_the_proof_of_the_click_and_syncs_the_user_flag(): void
    {
        $user = User::factory()->create(['email' => 'Marta@Example.com', 'newsletter' => false]);
        $subscriber = $this->subscribe('marta@example.com');

        $this->assertSame($user->id, $subscriber->user_id);
        $this->assertFalse($user->fresh()->newsletter);

        $confirmed = $this->service->confirm($subscriber->token, '203.0.113.99', 'Safari/18');

        $this->assertNotNull($confirmed);
        $this->assertSame(NewsletterSubscriber::STATUS_CONFIRMED, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
        $this->assertSame('203.0.113.99', $confirmed->confirmation_ip);
        $this->assertSame('Safari/18', $confirmed->confirmation_user_agent);
        // La prova della richiesta resta quella del form.
        $this->assertSame('198.51.100.7', $confirmed->consent_ip);
        $this->assertTrue($user->fresh()->newsletter);
    }

    public function test_confirming_twice_keeps_the_first_proof(): void
    {
        $subscriber = $this->subscribe('marta@example.com');
        $this->service->confirm($subscriber->token, '203.0.113.1', 'A');
        $first = $subscriber->fresh()->confirmed_at;

        $this->travel(1)->day();
        $this->assertNotNull($this->service->confirm($subscriber->token, '203.0.113.2', 'B'));

        $this->assertEquals($first, $subscriber->fresh()->confirmed_at);
        $this->assertSame('203.0.113.1', $subscriber->fresh()->confirmation_ip);
    }

    public function test_unknown_unsubscribed_or_suppressed_tokens_do_not_confirm(): void
    {
        $unsubscribed = NewsletterSubscriber::factory()->unsubscribed()->create();
        $bounced = NewsletterSubscriber::factory()->bounced()->create();

        $this->assertNull($this->service->confirm('sconosciuto', null, null));
        $this->assertNull($this->service->confirm($unsubscribed->token, null, null));
        $this->assertNull($this->service->confirm($bounced->token, null, null));
        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $unsubscribed->fresh()->status);
    }

    /** Vecchia casella: nessun form, il consenso è il clic sul link. */
    public function test_a_legacy_contact_gets_the_click_as_consent_proof(): void
    {
        $legacy = NewsletterSubscriber::factory()->legacy()->create(['locale' => 'it', 'confirmation_sent_at' => now()]);

        $this->service->confirm($legacy->token, '203.0.113.5', 'Chrome/140');

        $legacy->refresh();
        $this->assertSame(__('newsletter.confirm.consent', [], 'it'), $legacy->consent_text);
        $this->assertSame('203.0.113.5', $legacy->consent_ip);
        $this->assertSame('203.0.113.5', $legacy->confirmation_ip);
    }

    /** Il link della mail scade: dopo confirmation_ttl_days non iscrive più nessuno. */
    public function test_an_expired_confirmation_link_does_not_confirm(): void
    {
        config(['newsletter.confirmation_ttl_days' => 30]);
        $subscriber = $this->subscribe('marta@example.com');

        $this->travel(31)->days();

        $this->assertNull($this->service->findByConfirmationToken($subscriber->token));
        $this->assertNull($this->service->confirm($subscriber->token, '203.0.113.1', 'A'));
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->fresh()->status);
    }

    /** Un contatto mai raggiunto da una mail non ha un link da cliccare. */
    public function test_a_token_never_mailed_does_not_confirm(): void
    {
        $legacy = NewsletterSubscriber::factory()->legacy()->create();

        $this->assertNull($this->service->confirm($legacy->token, '203.0.113.1', 'A'));
    }

    public function test_coming_back_after_unsubscribing_invalidates_the_old_links(): void
    {
        $old = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'luca@example.com']);
        $oldToken = $old->token;

        $subscriber = $this->subscribe('luca@example.com');

        $this->assertNotSame($oldToken, $subscriber->token);
        $this->assertNull($subscriber->confirmed_at);
        $this->assertNull($subscriber->confirmation_ip);
        $this->assertNull($this->service->confirm($oldToken, null, null));
        $this->assertNotNull($this->service->confirm($subscriber->token, null, null));
    }

    public function test_unsubscribing_is_immediate_and_clears_the_user_flag(): void
    {
        $user = User::factory()->create(['newsletter' => true]);
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => $user->email, 'user_id' => $user->id]);

        $this->service->unsubscribe($subscriber);

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
        $this->assertFalse($user->fresh()->newsletter);
    }

    public function test_legacy_confirmations_go_once_and_only_to_never_contacted_legacy_contacts(): void
    {
        $never = NewsletterSubscriber::factory()->legacy()->count(2)->create();
        NewsletterSubscriber::factory()->legacy()->create(['confirmation_sent_at' => now()->subWeek()]);
        NewsletterSubscriber::factory()->create();
        NewsletterSubscriber::factory()->legacy()->confirmed()->create();

        $this->assertSame(2, $this->service->sendLegacyConfirmations());

        Mail::assertQueued(NewsletterConfirmationMail::class, 2);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->courtesy);
        $never->each(fn (NewsletterSubscriber $s) => $this->assertNotNull($s->fresh()->confirmation_sent_at));

        $this->assertSame(0, $this->service->sendLegacyConfirmations());
        Mail::assertQueued(NewsletterConfirmationMail::class, 2);
    }

    /**
     * Registrazioni arrivate con la vecchia casella dopo la migration: entrano
     * "da confermare", senza prova, e il flag dell'utente torna falso.
     */
    public function test_legacy_flags_are_imported_as_pending_without_proof(): void
    {
        $flagged = User::factory()->create(['email' => 'Paolo@Example.com', 'newsletter' => true]);
        $alreadyListed = User::factory()->create(['newsletter' => true]);
        NewsletterSubscriber::factory()->confirmed()->create(['email' => $alreadyListed->email, 'user_id' => $alreadyListed->id]);
        User::factory()->create(['newsletter' => false]);

        $this->assertSame(1, $this->service->importLegacyFlags());

        $subscriber = NewsletterSubscriber::firstWhere('email', 'paolo@example.com');
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->status);
        $this->assertTrue($subscriber->legacy);
        $this->assertSame(NewsletterSubscriber::SOURCE_LEGACY, $subscriber->source);
        $this->assertSame($flagged->id, $subscriber->user_id);
        $this->assertNull($subscriber->consent_text);
        $this->assertNull($subscriber->confirmation_sent_at);
        $this->assertFalse($flagged->fresh()->newsletter);
        $this->assertTrue($alreadyListed->fresh()->newsletter);

        $this->assertSame(0, $this->service->importLegacyFlags());
        Mail::assertNothingOutgoing();
    }

    /** Un indirizzo già iscritto dal piede del sito non torna "da confermare". */
    public function test_importing_a_legacy_flag_keeps_an_existing_subscription(): void
    {
        $user = User::factory()->create(['email' => 'giulia@example.com', 'newsletter' => true]);
        $existing = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'giulia@example.com']);

        $this->assertSame(0, $this->service->importLegacyFlags());

        $this->assertSame(NewsletterSubscriber::STATUS_CONFIRMED, $existing->fresh()->status);
        $this->assertSame($user->id, $existing->fresh()->user_id);
        $this->assertTrue($user->fresh()->newsletter);
    }
}
