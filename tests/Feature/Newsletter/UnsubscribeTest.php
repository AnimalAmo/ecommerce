<?php

namespace Tests\Feature\Newsletter;

use App\Livewire\Newsletter\Unsubscribe;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\NewsletterUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Disiscrizione: pagina dal link in fondo alla mail e endpoint a un clic. */
class UnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private function urls(): NewsletterUrls
    {
        return app(NewsletterUrls::class);
    }

    public function test_the_signed_link_shows_the_button_without_unsubscribing(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'luca@example.com']);

        $this->get($this->urls()->unsubscribePage($subscriber))
            ->assertOk()
            ->assertSee(__('newsletter.unsubscribe.title'))
            ->assertSee('luca@example.com')
            ->assertSee(__('newsletter.unsubscribe.submit'));

        $this->assertTrue($subscriber->fresh()->isConfirmed());
    }

    public function test_the_button_unsubscribes_at_once_and_clears_the_user_flag(): void
    {
        $user = User::factory()->create(['newsletter' => true]);
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => $user->email, 'user_id' => $user->id]);

        Livewire::test(Unsubscribe::class, ['subscriber' => $subscriber])
            ->call('unsubscribe')
            ->assertSet('done', true)
            ->assertSee(__('newsletter.unsubscribe.done_title'));

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
        $this->assertFalse($user->fresh()->newsletter);
    }

    /** La firma impedisce di disiscrivere gli altri cambiando l'id nell'URL. */
    public function test_an_unsigned_or_tampered_link_is_refused(): void
    {
        $mine = NewsletterSubscriber::factory()->confirmed()->create();
        $other = NewsletterSubscriber::factory()->confirmed()->create();

        $this->get(route('newsletter.unsubscribe', ['subscriber' => $other->id]))->assertForbidden();

        $tampered = str_replace('/'.$mine->id.'?', '/'.$other->id.'?', $this->urls()->unsubscribePage($mine));
        $this->get($tampered)->assertForbidden();

        $this->post(str_replace('/'.$mine->id.'?', '/'.$other->id.'?', $this->urls()->oneClick($mine)))->assertForbidden();

        $this->assertTrue($other->fresh()->isConfirmed());
    }

    /** L'iscritto inglese atterra sulla pagina inglese, firmata anche lì. */
    public function test_the_link_follows_the_subscriber_language(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->english()->create();

        $url = $this->urls()->unsubscribePage($subscriber);

        $this->assertStringStartsWith(url('/en/newsletter/unsubscribe/'.$subscriber->id).'?signature=', $url);
    }

    /** RFC 8058: il provider chiama in POST, senza sessione né CSRF. */
    public function test_one_click_post_unsubscribes_without_a_session(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $this->post($this->urls()->oneClick($subscriber), ['List-Unsubscribe' => 'One-Click'])
            ->assertOk();

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $subscriber->fresh()->status);
    }

    /** Un GET sullo stesso URL (client che lo apre nel browser) non disiscrive: porta alla pagina. */
    public function test_one_click_get_leads_to_the_page_without_unsubscribing(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $this->get($this->urls()->oneClick($subscriber))
            ->assertRedirect($this->urls()->unsubscribePage($subscriber));

        $this->assertTrue($subscriber->fresh()->isConfirmed());
    }

    /** Chi rimbalza o ha segnalato spam resta in lista di soppressione. */
    public function test_a_suppressed_address_stays_suppressed(): void
    {
        $subscriber = NewsletterSubscriber::factory()->bounced()->create();

        $this->post($this->urls()->oneClick($subscriber))->assertOk();

        $this->assertSame(NewsletterSubscriber::STATUS_BOUNCED, $subscriber->fresh()->status);
    }
}
