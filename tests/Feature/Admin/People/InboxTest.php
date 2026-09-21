<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\Inbox;
use App\Mail\PartnerInvitationMail;
use App\Models\ContactMessage\ContactMessage;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use App\Services\Admin\AdminCounters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $attributes = []): ContactMessage
    {
        $message = ContactMessage::create(array_merge([
            'first_name' => 'Anna',
            'last_name' => 'Persico',
            'email' => 'anna@example.com',
            'reason' => 'Informazioni',
            'message' => 'Posso portare due cani di taglia grande?',
        ], $attributes));

        return $this->withState($message, $attributes);
    }

    private function application(array $attributes = []): PartnerApplication
    {
        $application = PartnerApplication::create(array_merge([
            'first_name' => 'Marco',
            'last_name' => 'Galli',
            'email' => 'marco@example.com',
            'phone' => '+393331112222',
            'city' => 'Belgioioso',
            'business_name' => 'Agriturismo Le Corti',
            'role' => 'Titolare',
            'offer_type' => 'Struttura ricettiva',
            'description' => 'Cascina con sei camere e parco recintato.',
            'status' => PartnerApplication::STATUS_PENDING,
        ], $attributes));

        return $this->withState($application, $attributes);
    }

    /** handled_at / archived_at / created_at non sono fillable: li scrive solo il pannello. */
    private function withState(ContactMessage|PartnerApplication $item, array $attributes): ContactMessage|PartnerApplication
    {
        $state = array_intersect_key($attributes, array_flip(['handled_at', 'archived_at', 'created_at']));

        if ($state !== []) {
            $item->forceFill($state)->save();
        }

        return $item;
    }

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): \Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_a_customer_and_a_partner_are_refused(): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route('admin.inbox'))->assertForbidden();

        $this->actingAsActivePartner();
        $this->get(route('admin.inbox'))->assertForbidden();
    }

    public function test_messages_to_work_are_listed_and_the_newest_is_open(): void
    {
        $this->actingAsSuperadmin();

        $this->message(['first_name' => 'Vecchio', 'created_at' => now()->subDays(3)]);
        $this->message(['first_name' => 'Nuovo', 'last_name' => 'Arrivo', 'message' => 'Testo del più recente']);
        $this->message(['first_name' => 'Lavorato', 'handled_at' => now()]);
        $this->message(['first_name' => 'Archiviato', 'archived_at' => now()]);
        $this->application();

        $this->get(route('admin.inbox'))
            ->assertOk()
            ->assertSee('Contatti e candidature')
            ->assertSee('2 messaggi da lavorare')
            ->assertSee('Vecchio')
            ->assertSee('Nuovo Arrivo')
            ->assertSee('Testo del più recente')
            ->assertDontSee('Lavorato')
            ->assertDontSee('Archiviato');
    }

    public function test_the_filter_shows_everything_or_the_archive(): void
    {
        $this->actingAsSuperadmin();

        $this->message(['first_name' => 'Aperto']);
        $this->message(['first_name' => 'Lavorato', 'handled_at' => now()]);
        $this->message(['first_name' => 'Archiviato', 'archived_at' => now()]);

        Livewire::test(Inbox::class)
            ->set('filter', 'all')
            ->assertSee('3 messaggi in tutto')
            ->assertSee(['Aperto', 'Lavorato', 'Archiviato'])
            ->set('filter', 'archived')
            ->assertSee('1 messaggio in archivio')
            ->assertSee('Archiviato')
            ->assertDontSee('Aperto');
    }

    public function test_an_unknown_tab_or_filter_in_the_url_falls_back_to_the_default(): void
    {
        $this->actingAsSuperadmin();
        $this->message(['first_name' => 'Aperto']);

        $this->get(route('admin.inbox', ['tab' => 'spam', 'filter' => 'nope']))
            ->assertOk()
            ->assertSee('1 messaggio da lavorare')
            ->assertSee('Aperto');
    }

    public function test_the_applications_tab_shows_the_application_details(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create();
        $this->application(['user_id' => $user->id, 'website' => 'https://lecorti.example']);

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertSee('1 candidatura da lavorare')
            ->assertSee('Agriturismo Le Corti')
            ->assertSee('Struttura ricettiva')
            ->assertSee('Belgioioso')
            ->assertSee('https://lecorti.example')
            ->assertSee('Cascina con sei camere e parco recintato.')
            ->assertSee('Da invitare')
            ->assertSee(route('admin.users.show', $user), escape: false);
    }

    public function test_selecting_an_item_opens_it(): void
    {
        $this->actingAsSuperadmin();

        // Oltre i 60 caratteri dell'anteprima: la coda si legge solo nel dettaglio.
        $first = $this->message(['message' => str_repeat('Anteprima del primo. ', 4).'Coda del primo']);
        $second = $this->message(['message' => str_repeat('Anteprima del secondo. ', 4).'Coda del secondo', 'created_at' => now()->subDay()]);

        Livewire::test(Inbox::class)
            ->assertSet('selected', $first->id)
            ->assertSee('Coda del primo')
            ->assertDontSee('Coda del secondo')
            ->call('select', $second->id)
            ->assertSet('selected', $second->id)
            ->assertSee('Coda del secondo')
            ->assertDontSee('Coda del primo');
    }

    public function test_an_item_opened_from_the_url_is_shown(): void
    {
        $this->actingAsSuperadmin();

        $this->message(['message' => 'Più recente']);
        $older = $this->message(['message' => 'Quello del link', 'created_at' => now()->subDay()]);

        $this->get(route('admin.inbox', ['id' => $older->id]))
            ->assertOk()
            ->assertSee('Quello del link');
    }

    public function test_mark_as_handled_and_reopen(): void
    {
        $this->actingAsSuperadmin();
        $message = $this->message();

        $component = Livewire::test(Inbox::class)
            ->call('toggleHandled')
            ->assertDispatched('toast-show', $this->toast('Segnata come lavorata.'));
        $this->assertNotNull($message->fresh()->handled_at);

        // Resta aperta nel dettaglio anche se esce dall'elenco "Da lavorare".
        $component->assertSee('Riapri')
            ->call('toggleHandled');
        $this->assertNull($message->fresh()->handled_at);
    }

    public function test_archive_and_unarchive(): void
    {
        $this->actingAsSuperadmin();
        $application = $this->application();

        $component = Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->call('toggleArchived')
            ->assertDispatched('toast-show', $this->toast('Archiviata.'));
        $this->assertNotNull($application->fresh()->archived_at);

        $component->call('toggleArchived');
        $this->assertNull($application->fresh()->archived_at);
    }

    public function test_delete_asks_first_and_then_removes_the_row(): void
    {
        $this->actingAsSuperadmin();
        $message = $this->message(['first_name' => 'Giada', 'last_name' => 'Neri']);

        Livewire::test(Inbox::class)
            ->call('askDelete')
            ->assertSee('Cancellare questo messaggio?')
            ->assertSee('Il messaggio di Giada Neri e i dati di contatto verranno rimossi definitivamente.')
            ->call('delete')
            ->assertDispatched('toast-show', $this->toast('Cancellato definitivamente.'));

        $this->assertModelMissing($message);
    }

    public function test_invite_sends_the_partner_invitation_from_the_application(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        $application = $this->application();

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertSee('Invia invito')
            ->call('invite')
            ->assertDispatched('toast-show', $this->toast('Invito inviato a marco@example.com.'));

        Mail::assertQueued(PartnerInvitationMail::class, fn (PartnerInvitationMail $mail) => $mail->hasTo('marco@example.com')
            && $mail->application->is($application)
            && str_contains($mail->link, 'signature='));

        $application->refresh();
        $this->assertSame(PartnerApplication::STATUS_INVITED, $application->status);
        $this->assertNotNull($application->invited_at);
    }

    public function test_an_invited_candidate_can_be_invited_again(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        $this->application(['status' => PartnerApplication::STATUS_INVITED, 'invited_at' => now()->subWeek()]);

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertSee('Invia di nuovo l&#039;invito', escape: false)
            ->call('invite');

        Mail::assertQueued(PartnerInvitationMail::class);
    }

    public function test_a_registered_candidate_is_not_invited_again(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        $application = $this->application(['status' => PartnerApplication::STATUS_REGISTERED]);

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertSee('Registrato')
            ->call('invite')
            ->assertDispatched('toast-show', $this->toast('Questo candidato è già registrato come partner.'));

        Mail::assertNothingQueued();
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->fresh()->status);
    }

    public function test_reply_opens_the_mail_client_on_the_sender(): void
    {
        $this->actingAsSuperadmin();
        $this->message(['email' => 'anna@example.com', 'reason' => 'Prenotazioni']);

        Livewire::test(Inbox::class)
            ->assertSee('mailto:anna@example.com?subject='.rawurlencode('Re: Prenotazioni'), escape: false);
    }

    public function test_the_navigation_badge_counts_what_is_still_to_work(): void
    {
        $this->actingAsSuperadmin();

        $this->message();
        $this->message(['handled_at' => now()]);
        $this->message(['archived_at' => now()]);
        $this->application();
        $this->application(['handled_at' => now()]);

        $counters = app(AdminCounters::class);
        $this->assertSame(1, $counters->openMessages());
        $this->assertSame(1, $counters->openApplications());
        $this->assertSame(2, $counters->openInbox());
    }
}
