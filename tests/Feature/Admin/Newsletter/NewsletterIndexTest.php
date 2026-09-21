<?php

namespace Tests\Feature\Admin\Newsletter;

use App\Livewire\Admin\Newsletter\NewsletterIndex;
use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewsletterIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_superadmin_opens_the_screen(): void
    {
        $this->get(route('admin.newsletter.index'))->assertRedirect(route('admin.login'));

        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');
        $this->actingAs($customer)->get(route('admin.newsletter.index'))->assertForbidden();

        $this->actingAsActivePartner();
        $this->get(route('admin.newsletter.index'))->assertForbidden();

        $this->actingAsSuperadmin();
        $this->get(route('admin.newsletter.index'))->assertOk()->assertSee(__('admin-newsletter.subscribers.heading'));
    }

    public function test_the_screen_shows_the_numbers_of_the_list(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->count(3)->create();
        NewsletterSubscriber::factory()->create();
        NewsletterSubscriber::factory()->unsubscribed()->create();
        NewsletterCampaign::factory()->sent(100, 41)->create([
            'subject' => ['it' => 'Cinque mete sul Garda'],
            'delivered_count' => 98,
            'started_at' => now()->setDate(2026, 8, 28),
        ]);

        $this->get(route('admin.newsletter.index'))
            ->assertOk()
            ->assertSee('3 iscritti confermati. L\'ultimo invio è del 28 agosto.')
            ->assertSee('42%')
            ->assertSee('41 su 98 consegnate')
            ->assertSee('Cinque mete sul Garda')
            ->assertSee(__('admin-newsletter.campaign_status.sent'));
    }

    public function test_each_subscriber_shows_state_origin_and_when_consent_was_given(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->create([
            'email' => 'luca@example.com',
            'confirmed_at' => now()->setDate(2026, 9, 4)->setTime(18, 22),
        ]);
        NewsletterSubscriber::factory()->legacy()->create(['email' => 'paolo@example.com']);

        Livewire::test(NewsletterIndex::class)
            ->assertSee('luca@example.com')
            ->assertSee('piede del sito')
            ->assertSee('confermato il 4 set 2026, 18:22')
            ->assertSee('paolo@example.com')
            ->assertSee('vecchia casella di registrazione')
            ->assertSee('nessuna prova');
    }

    public function test_filters_narrow_the_subscribers(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'luca@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'marta@example.com', 'source' => NewsletterSubscriber::SOURCE_REGISTRATION]);
        NewsletterSubscriber::factory()->bounced()->english()->create(['email' => 'john@example.com']);

        Livewire::test(NewsletterIndex::class)
            ->set('status', 'confirmed')
            ->assertSee('luca@example.com')
            ->assertDontSee('marta@example.com')
            ->set('status', 'suppressed')
            ->assertSee('john@example.com')
            ->assertDontSee('luca@example.com')
            ->set('status', '')
            ->set('source', 'registration')
            ->assertSee('marta@example.com')
            ->assertDontSee('luca@example.com')
            ->set('source', '')
            ->set('locale', 'en')
            ->assertSee('john@example.com')
            ->assertDontSee('marta@example.com')
            ->set('locale', '')
            ->set('search', 'LUCA')
            ->assertSee('luca@example.com')
            ->assertDontSee('john@example.com')
            ->assertSee(route('admin.newsletter.export', ['search' => 'LUCA']), false);
    }

    public function test_the_proof_modal_shows_the_whole_proof(): void
    {
        $this->actingAsSuperadmin();
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create([
            'email' => 'luca@example.com',
            'consent_text' => 'Iscrivendomi accetto di ricevere la newsletter di AnimalAmo.',
            'consent_ip' => '198.51.100.7',
            'confirmation_ip' => '203.0.113.99',
        ]);

        Livewire::test(NewsletterIndex::class)
            ->call('showProof', $subscriber->id)
            ->assertSee(__('admin-newsletter.proof.title'))
            ->assertSee('Iscrivendomi accetto di ricevere la newsletter di AnimalAmo.')
            ->assertSee('198.51.100.7')
            ->assertSee('203.0.113.99');
    }

    public function test_the_admin_can_unsubscribe_an_address_after_confirming(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create(['newsletter' => true]);
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['email' => $user->email, 'user_id' => $user->id]);

        Livewire::test(NewsletterIndex::class)
            ->call('askUnsubscribe', $subscriber->id)
            ->assertSee(__('admin-newsletter.unsubscribe_modal.title'))
            ->call('unsubscribe')
            ->assertSet('selectedId', null);

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $subscriber->fresh()->status);
        $this->assertFalse($user->fresh()->newsletter);
        $this->assertTrue($user->fresh()->exists);
    }

    public function test_legacy_contacts_get_the_courtesy_confirmation_from_the_notice(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->legacy()->count(2)->create();
        User::factory()->create(['newsletter' => true]);

        Livewire::test(NewsletterIndex::class)
            ->assertSee('3 contatti della vecchia casella di registrazione non hanno la prova del consenso')
            ->call('sendLegacyConfirmations')
            ->assertDontSee(__('admin-newsletter.legacy.button'));

        Mail::assertQueued(NewsletterConfirmationMail::class, 3);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->courtesy);
    }

    public function test_the_legacy_notice_is_hidden_when_there_is_nobody_to_ask(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->create();

        Livewire::test(NewsletterIndex::class)->assertDontSee(__('admin-newsletter.legacy.button'));
    }
}
