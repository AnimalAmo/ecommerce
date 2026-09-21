<?php

namespace Tests\Feature\Newsletter;

use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterCampaignRecipient;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/** Il numero della newsletter come arriva nella casella. */
class CampaignMailTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $attributes = []): NewsletterCampaign
    {
        return NewsletterCampaign::factory()->create(array_merge([
            'subject' => ['it' => 'Cinque camminate sul Garda', 'en' => 'Five walks on Lake Garda'],
            'preheader' => ['it' => 'Sentieri all\'ombra', 'en' => 'Shaded trails'],
            'body' => [
                'it' => '<h2>Autunno</h2><p>Il <a href="https://animalamo.it/vacanze" target="_blank" rel="noopener">lago</a> si svuota.</p>',
                'en' => '<h2>Autumn</h2><p>The lake empties.</p>',
            ],
        ], $attributes));
    }

    private function deliver(NewsletterCampaignMail $mail): Email
    {
        Mail::to('marta@example.com')->send($mail);

        return Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    }

    /** RFC 8058: Gmail e Yahoo mostrano "Annulla iscrizione" solo con i due header. */
    public function test_every_real_send_carries_the_one_click_unsubscribe_headers(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $message = $this->deliver(new NewsletterCampaignMail($this->campaign(), 'it', $subscriber));

        $this->assertSame(
            '<'.app(NewsletterUrls::class)->oneClick($subscriber).'>',
            $message->getHeaders()->get('List-Unsubscribe')->getBodyAsString(),
        );
        $this->assertSame('List-Unsubscribe=One-Click', $message->getHeaders()->get('List-Unsubscribe-Post')->getBodyAsString());
    }

    public function test_the_footer_links_to_the_signed_unsubscribe_page_and_says_who_writes(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();
        $mail = new NewsletterCampaignMail($this->campaign(), 'it', $subscriber);
        $unsubscribe = app(NewsletterUrls::class)->unsubscribePage($subscriber);

        $mail->assertSeeInHtml(e($unsubscribe), false);
        $mail->assertSeeInText($unsubscribe);
        $mail->assertSeeInHtml(__('newsletter.mail.layout.reason'));
        $mail->assertSeeInHtml('02746270228');
        $mail->assertSeeInHtml('Sentieri all\'ombra', false);
        $mail->assertSeeInHtml('<h2', false);
        $mail->assertSeeInText('lago (https://animalamo.it/vacanze)');
        $mail->assertDontSeeInHtml(__('newsletter.mail.layout.test_notice'));
    }

    public function test_the_whole_mail_follows_the_content_language(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->english()->create();
        $mail = new NewsletterCampaignMail($this->campaign(), 'en', $subscriber);

        $mail->assertHasSubject('Five walks on Lake Garda');
        $mail->assertSeeInHtml('The lake empties.');
        $mail->assertSeeInHtml(trans('newsletter.mail.layout.reason', [], 'en'));
        $mail->assertSeeInHtml(e(url('/en/newsletter/unsubscribe/'.$subscriber->id)), false);
    }

    public function test_a_test_send_is_flagged_and_has_no_unsubscribe_headers(): void
    {
        $mail = new NewsletterCampaignMail($this->campaign(), 'it');

        $mail->assertSeeInHtml(__('newsletter.mail.layout.test_notice'));

        $message = $this->deliver($mail);
        $this->assertNull($message->getHeaders()->get('List-Unsubscribe'));
    }

    public function test_the_panel_preview_has_no_test_notice(): void
    {
        (new NewsletterCampaignMail($this->campaign(), 'it', preview: true))
            ->assertDontSeeInHtml(__('newsletter.mail.layout.test_notice'))
            ->assertSeeInHtml('si svuota.', false);
    }

    /**
     * Via SMTP Mailgun legge tracking e variabili dagli header X-Mailgun-*: il
     * webhook attribuisce così consegne e aperture al destinatario.
     */
    public function test_over_smtp_the_mailgun_options_travel_as_headers(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();
        $campaign = $this->campaign();
        $recipient = NewsletterCampaignRecipient::create([
            'newsletter_campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
        ]);

        $headers = $this->deliver(new NewsletterCampaignMail($campaign, 'it', $subscriber, $recipient))->getHeaders();

        $this->assertSame('yes', $headers->get('X-Mailgun-Track-Opens')->getBodyAsString());
        $this->assertSame(
            ['newsletter_campaign_id' => (string) $campaign->id, 'newsletter_recipient_id' => (string) $recipient->id],
            json_decode($headers->get('X-Mailgun-Variables')->getBodyAsString(), true),
        );
    }

    /** Con il transport API di Mailgun il tracking passa come parametro o:, per questa mail sola. */
    public function test_over_the_mailgun_api_opens_are_tracked_per_message(): void
    {
        config(['newsletter.mailer' => 'mailgun-newsletter']);
        $message = new Email;

        foreach ((new NewsletterCampaignMail($this->campaign(), 'it', NewsletterSubscriber::factory()->confirmed()->create()))->envelope()->using as $callback) {
            $callback($message);
        }

        $this->assertSame('yes', $message->getHeaders()->get('o:tracking-opens')->getBodyAsString());
        $this->assertNull($message->getHeaders()->get('X-Mailgun-Variables'));
    }
}
