<?php

namespace Tests\Feature\Newsletter;

use App\Livewire\Newsletter\SubscribeForm;
use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/** Form di iscrizione nel piede del sito: la frase accanto al pulsante è la prova. */
class SubscribeFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // Il limite per IP vive nella cache, che RefreshDatabase non tocca.
        RateLimiter::clear('newsletter-subscribe|127.0.0.1');
    }

    public function test_the_footer_shows_the_form_with_the_consent_sentence(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeLivewire(SubscribeForm::class)
            ->assertSee(__('newsletter.footer.heading'))
            ->assertSee(__('newsletter.footer.consent'));
    }

    public function test_subscribing_stores_the_exact_sentence_shown_and_sends_the_confirmation(): void
    {
        Livewire::test(SubscribeForm::class)
            ->set('email', 'Luca.Ferrari@Example.com')
            ->call('subscribe')
            ->assertHasNoErrors()
            ->assertSet('done', true)
            ->assertSet('email', '')
            ->assertSee(__('newsletter.footer.success_title'));

        $subscriber = NewsletterSubscriber::sole();

        $this->assertSame('luca.ferrari@example.com', $subscriber->email);
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->status);
        $this->assertSame(NewsletterSubscriber::SOURCE_FOOTER, $subscriber->source);
        $this->assertSame(__('newsletter.footer.consent'), $subscriber->consent_text);
        $this->assertSame('127.0.0.1', $subscriber->consent_ip);
        $this->assertNotNull($subscriber->requested_at);
        $this->assertSame('it', $subscriber->locale);

        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->hasTo('luca.ferrari@example.com'));
    }

    public function test_the_sentence_is_stored_in_the_language_the_visitor_read(): void
    {
        app()->setLocale('en');

        Livewire::test(SubscribeForm::class)
            ->set('email', 'john@example.com')
            ->call('subscribe');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('en', $subscriber->locale);
        $this->assertSame(trans('newsletter.footer.consent', [], 'en'), $subscriber->consent_text);
    }

    /** Stessa risposta per chi è già iscritto: il form non dice chi è in lista. */
    public function test_an_address_already_confirmed_gets_the_same_answer_and_no_mail(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'luca@example.com']);

        Livewire::test(SubscribeForm::class)
            ->set('email', 'luca@example.com')
            ->call('subscribe')
            ->assertHasNoErrors()
            ->assertSet('done', true);

        Mail::assertNothingOutgoing();
    }

    public function test_an_invalid_address_shows_a_localized_error(): void
    {
        Livewire::test(SubscribeForm::class)
            ->set('email', 'non-una-email')
            ->call('subscribe')
            ->assertHasErrors(['email' => 'email'])
            ->assertSet('done', false)
            ->assertSee(__('validation.email', ['attribute' => 'email']));

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_too_many_requests_from_the_same_address_are_refused(): void
    {
        foreach (range(1, 5) as $i) {
            Livewire::test(SubscribeForm::class)->set('email', "prova{$i}@example.com")->call('subscribe');
        }

        Livewire::test(SubscribeForm::class)
            ->set('email', 'sesta@example.com')
            ->call('subscribe')
            ->assertHasErrors('email')
            ->assertSee(__('newsletter.footer.throttled', ['minutes' => 10]));

        $this->assertSame(5, NewsletterSubscriber::count());
    }
}
