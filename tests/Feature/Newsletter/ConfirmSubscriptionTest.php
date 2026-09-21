<?php

namespace Tests\Feature\Newsletter;

use App\Livewire\Newsletter\ConfirmSubscription;
use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\NewsletterUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Double opt-in: la mail porta alla pagina, il pulsante della pagina conferma. */
class ConfirmSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $attributes = []): NewsletterSubscriber
    {
        return NewsletterSubscriber::factory()->create(array_merge([
            'email' => 'marta@example.com',
            'confirmation_sent_at' => now(),
        ], $attributes));
    }

    /**
     * I filtri antispam aprono i link delle mail da soli: il GET non deve
     * iscrivere nessuno.
     */
    public function test_opening_the_link_shows_the_button_without_confirming(): void
    {
        $subscriber = $this->pending();

        $this->get(app(NewsletterUrls::class)->confirm($subscriber))
            ->assertOk()
            ->assertSee(__('newsletter.confirm.title'))
            ->assertSee('marta@example.com')
            ->assertSee(__('newsletter.confirm.submit'));

        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->fresh()->status);
    }

    public function test_the_button_confirms_and_records_the_click(): void
    {
        $user = User::factory()->create(['email' => 'marta@example.com', 'newsletter' => false]);
        $subscriber = $this->pending(['user_id' => $user->id]);

        Livewire::withHeaders(['User-Agent' => 'Safari/18'])
            ->test(ConfirmSubscription::class, ['token' => $subscriber->token])
            ->call('confirm')
            ->assertSet('confirmed', true)
            ->assertSee(__('newsletter.confirm.done_title'));

        $subscriber->refresh();
        $this->assertSame(NewsletterSubscriber::STATUS_CONFIRMED, $subscriber->status);
        $this->assertNotNull($subscriber->confirmed_at);
        $this->assertSame('127.0.0.1', $subscriber->confirmation_ip);
        $this->assertTrue($user->fresh()->newsletter);
    }

    public function test_an_already_confirmed_link_shows_the_outcome_again(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $this->get(app(NewsletterUrls::class)->confirm($subscriber))
            ->assertOk()
            ->assertSee(__('newsletter.confirm.done_title'))
            ->assertDontSee(__('newsletter.confirm.submit'));
    }

    public function test_an_unknown_expired_or_suppressed_link_is_refused(): void
    {
        $expired = $this->pending(['email' => 'vecchio@example.com', 'confirmation_sent_at' => now()->subDays(60)]);
        $bounced = NewsletterSubscriber::factory()->bounced()->create();

        $page = fn (string $token) => $this->get(route('newsletter.confirm', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('newsletter.confirm.invalid_title'))
            ->assertDontSee(__('newsletter.confirm.submit'));

        $page('sconosciuto');
        $page($expired->token);
        $page($bounced->token);

        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $expired->fresh()->status);
    }

    /** La mail parte nella lingua dell'iscritto e porta alla pagina nella sua lingua. */
    public function test_the_confirmation_mail_links_to_the_page_in_the_subscriber_language(): void
    {
        $italian = $this->pending();
        $english = $this->pending(['email' => 'john@example.com', 'locale' => 'en']);

        $html = (new NewsletterConfirmationMail($italian))->render();
        $this->assertStringContainsString(url('/newsletter/conferma/'.$italian->token), $html);
        $this->assertStringContainsString(__('newsletter.mail.confirm.heading', [], 'it'), $html);
        $this->assertStringContainsString('02746270228', $html);

        $mail = new NewsletterConfirmationMail($english);
        $mail->assertSeeInHtml(url('/en/newsletter/confirm/'.$english->token), false);
        $mail->assertSeeInText(trans('newsletter.mail.confirm.cta', [], 'en'));
        $mail->assertHasSubject(trans('newsletter.mail.confirm.subject', [], 'en'));
    }

    public function test_the_courtesy_mail_explains_why_it_arrives(): void
    {
        $legacy = NewsletterSubscriber::factory()->legacy()->create();

        (new NewsletterConfirmationMail($legacy, courtesy: true))
            ->assertSeeInText(__('newsletter.mail.confirm.courtesy_intro'))
            ->assertDontSeeInText(__('newsletter.mail.confirm.intro'));
    }

    /** Sul sottodominio della newsletter, quando è configurato. */
    public function test_the_mail_uses_the_newsletter_sender_when_configured(): void
    {
        config([
            'newsletter.from.address' => 'newsletter@news.animalamo.it',
            'newsletter.from.name' => 'AnimalAmo',
            'newsletter.reply_to' => 'redazione@animalamo.it',
        ]);

        Mail::to('marta@example.com')->sendNow(new NewsletterConfirmationMail($this->pending()));

        $sent = Mail::mailer()->getSymfonyTransport()->messages()[0]->getOriginalMessage();

        $this->assertSame('newsletter@news.animalamo.it', $sent->getFrom()[0]->getAddress());
        $this->assertCount(1, $sent->getReplyTo());
        $this->assertSame('redazione@animalamo.it', $sent->getReplyTo()[0]->getAddress());
    }
}
