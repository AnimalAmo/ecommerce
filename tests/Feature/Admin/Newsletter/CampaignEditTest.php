<?php

namespace Tests\Feature\Admin\Newsletter;

use App\Livewire\Admin\Newsletter\CampaignEdit;
use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\DmarcChecker;
use App\Services\Newsletter\NewsletterMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CampaignEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /** Il controllo DMARC è una query DNS: nei test risponde un finto. */
    private function fakeDmarc(bool $present): void
    {
        $this->app->instance(DmarcChecker::class, new class($present) extends DmarcChecker
        {
            public function __construct(private bool $present) {}

            protected function lookup(string $name): array
            {
                return $this->present ? ['v=DMARC1; p=none'] : [];
            }
        });
    }

    public function test_only_a_superadmin_opens_the_editor(): void
    {
        $campaign = NewsletterCampaign::factory()->create();

        $this->get(route('admin.newsletter.create'))->assertRedirect(route('admin.login'));
        $this->actingAsActivePartner();
        $this->get(route('admin.newsletter.edit', $campaign))->assertForbidden();

        $this->actingAsSuperadmin();
        $this->get(route('admin.newsletter.create'))->assertOk()->assertSee(__('admin-newsletter.editor.title_new'));
        $this->get(route('admin.newsletter.edit', $campaign))->assertOk()->assertSee('Cinque camminate sul Garda');
    }

    public function test_a_draft_is_saved_in_both_languages_with_a_clean_body(): void
    {
        $admin = $this->actingAsSuperadmin();

        Livewire::test(CampaignEdit::class)
            ->set('subject.it', 'Cinque camminate sul Garda')
            ->set('preheader.it', 'Sentieri all\'ombra')
            ->set('body.it', '<p style="color:red">L\'autunno<script>alert(1)</script></p>')
            ->set('subject.en', 'Five walks on Lake Garda')
            ->set('body.en', '<p>Autumn</p>')
            ->set('audience', NewsletterCampaign::AUDIENCE_IT)
            ->set('hourlyRate', 500)
            ->call('save')
            ->assertHasNoErrors();

        $campaign = NewsletterCampaign::sole();
        $this->assertTrue($campaign->isDraft());
        $this->assertSame('Cinque camminate sul Garda', $campaign->getTranslation('subject', 'it'));
        $this->assertSame('Five walks on Lake Garda', $campaign->getTranslation('subject', 'en'));
        $this->assertSame('<p>L\'autunno</p>', $campaign->getTranslation('body', 'it'));
        $this->assertSame(NewsletterCampaign::AUDIENCE_IT, $campaign->audience);
        $this->assertSame(500, $campaign->hourly_rate);
        $this->assertSame($admin->id, $campaign->created_by);
    }

    public function test_the_italian_version_is_required_and_errors_are_localized(): void
    {
        $this->actingAsSuperadmin();

        Livewire::test(CampaignEdit::class)
            ->set('body.it', '<p></p>')
            ->call('save')
            ->assertHasErrors(['subject.it' => 'required', 'body.it' => 'required'])
            ->assertSee(__('validation.required', ['attribute' => __('admin-newsletter.editor.attributes.subject_it')]));

        $this->assertSame(0, NewsletterCampaign::count());
    }

    public function test_clearing_the_english_version_removes_it(): void
    {
        $this->actingAsSuperadmin();
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->assertSet('subject.en', 'Five walks on Lake Garda')
            ->set('subject.en', '')
            ->set('body.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($campaign->fresh()->hasVersion('en'));
        $this->assertSame('', $campaign->fresh()->getTranslation('subject', 'en', false));
    }

    public function test_the_preview_renders_the_real_mail_of_what_is_being_written(): void
    {
        $this->actingAsSuperadmin();

        Livewire::test(CampaignEdit::class)
            ->set('subject.it', 'Oggetto in bozza')
            ->set('body.it', '<p>Testo non ancora salvato</p>')
            ->call('openPreview', 'it')
            ->assertSet('previewLocale', 'it')
            ->assertSee('Testo non ancora salvato')
            ->assertSee('02746270228');

        $this->assertSame(0, NewsletterCampaign::count());
    }

    public function test_a_test_send_saves_the_draft_and_mails_the_chosen_version(): void
    {
        $this->actingAsSuperadmin(['email' => 'silvia@animalamo.it']);

        Livewire::test(CampaignEdit::class)
            ->set('subject.it', 'Cinque camminate sul Garda')
            ->set('body.it', '<p>Testo</p>')
            ->call('openTest')
            ->assertSet('testEmail', 'silvia@animalamo.it')
            ->set('testLocale', 'it')
            ->call('sendTest')
            ->assertHasNoErrors();

        $campaign = NewsletterCampaign::sole();
        $this->assertSame('silvia@animalamo.it', $campaign->test_sent_to);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('silvia@animalamo.it')
            && $mail->subscriber === null
            && $mail->campaign->is($campaign));
    }

    public function test_a_failed_test_send_says_so(): void
    {
        $this->actingAsSuperadmin();
        $this->mock(NewsletterMailer::class)->shouldReceive('send')->andThrow(new RuntimeException('Forbidden'));
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->set('testEmail', 'silvia@animalamo.it')
            ->call('sendTest')
            ->assertHasErrors('testEmail')
            ->assertSee(__('admin-newsletter.errors.test_failed'));

        $this->assertNull($campaign->fresh()->test_sent_at);
    }

    public function test_the_checklist_follows_test_english_version_and_dmarc(): void
    {
        $this->actingAsSuperadmin();
        $this->fakeDmarc(present: false);
        $campaign = NewsletterCampaign::factory()->create(['body' => ['it' => '<p>Solo italiano</p>']]);

        $component = Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->assertSee(__('admin-newsletter.checklist.test_missing'))
            ->assertSee(__('admin-newsletter.checklist.english_missing'))
            ->call('checkDmarc')
            ->assertSee(__('admin-newsletter.checklist.dmarc_missing', ['domain' => app(DmarcChecker::class)->domain()]));

        $component->set('testEmail', 'silvia@animalamo.it')
            ->call('sendTest')
            ->assertSee(__('admin-newsletter.checklist.test_done', ['email' => 'silvia@animalamo.it']));

        $this->travel(1)->minute();
        $component->set('body.it', '<p>Testo cambiato</p>')
            ->call('save')
            ->assertSee(__('admin-newsletter.checklist.test_outdated', ['email' => 'silvia@animalamo.it']));

        $component->set('audience', NewsletterCampaign::AUDIENCE_IT)
            ->assertSee(__('admin-newsletter.checklist.english_not_needed'));

        // Il risultato resta in cache un'ora: il record appena pubblicato si vede dopo.
        Cache::flush();
        $this->fakeDmarc(present: true);
        $component->call('checkDmarc')
            ->assertSee(__('admin-newsletter.checklist.dmarc_done', ['domain' => app(DmarcChecker::class)->domain()]));
    }

    public function test_the_recipients_card_counts_the_confirmed_addresses_and_estimates_the_time(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->count(3)->create();
        NewsletterSubscriber::factory()->confirmed()->english()->create();
        NewsletterSubscriber::factory()->create();

        Livewire::test(CampaignEdit::class)
            ->assertSee('Tutti i confermati (4)')
            ->assertSee('Solo italiano (3)')
            ->assertSee('Solo inglese (1)')
            ->assertSee('andrà a 4 indirizzi confermati')
            ->assertSee('A 200 all\'ora l\'invio si chiude subito.');
    }

    public function test_send_to_all_asks_for_confirmation_then_starts(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->call('askLaunch')
            ->assertDispatched('modal-show', name: 'newsletter-launch')
            ->assertSee('Inviare a 2 indirizzi?')
            ->call('launch')
            ->assertRedirect(route('admin.newsletter.edit', $campaign));

        $this->assertNotSame(NewsletterCampaign::STATUS_DRAFT, $campaign->fresh()->status);
        Mail::assertSent(NewsletterCampaignMail::class, 2);
    }

    public function test_send_to_all_is_refused_with_nobody_to_send_to(): void
    {
        $this->actingAsSuperadmin();
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->call('askLaunch')
            ->assertNotDispatched('modal-show', name: 'newsletter-launch')
            ->assertDispatched('toast-show');

        $this->assertTrue($campaign->fresh()->isDraft());
        Mail::assertNothingSent();
    }

    public function test_in_production_send_to_all_says_why_the_mail_setup_blocks_it(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->create();
        $campaign = NewsletterCampaign::factory()->create();
        $this->app['env'] = 'production';
        config(['newsletter.mailer' => null, 'queue.default' => 'database']);

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->call('askLaunch')
            ->assertNotDispatched('modal-show', name: 'newsletter-launch')
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('admin-newsletter.errors.shared_mailer'));

        $this->assertTrue($campaign->fresh()->isDraft());
        Mail::assertNothingSent();
    }

    public function test_a_campaign_that_left_opens_read_only_with_its_numbers(): void
    {
        $this->actingAsSuperadmin();
        $campaign = NewsletterCampaign::factory()->sent(608, 251)->create(['delivered_count' => 600, 'failed_count' => 2]);

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->assertSee('Cinque camminate sul Garda')
            ->assertSee('608')
            ->assertSee('42%')
            ->assertDontSee(__('admin-newsletter.editor.send_all'))
            ->call('save')
            ->assertForbidden();
    }

    /** Un invio fermo (worker giù) si riprende dal pannello, senza doppioni. */
    public function test_a_stalled_campaign_can_be_resumed_from_the_panel(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->count(2)->create();
        $campaign = NewsletterCampaign::factory()->create([
            'status' => NewsletterCampaign::STATUS_SENDING,
            'started_at' => now()->subDay(),
        ]);

        Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
            ->assertSee(__('admin-newsletter.report.stalled_heading'))
            ->call('resume');

        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->fresh()->status);
        Mail::assertSent(NewsletterCampaignMail::class, 2);
    }
}
