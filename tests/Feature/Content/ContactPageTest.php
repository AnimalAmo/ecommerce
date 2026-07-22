<?php

namespace Tests\Feature\Content;

use App\Livewire\Content\Contact;
use App\Models\ContactMessage\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Contattaci: pagina unica per i vecchi link Contattaci/Assistenza,
 * form stile "Lavora con noi" + dati di contatto della cliente.
 */
class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_with_the_contact_info(): void
    {
        $this->get('/contattaci')
            ->assertOk()
            ->assertSeeText('Contattaci')
            ->assertSee('animalamo24@gmail.com')
            ->assertSee('@animal___amo')
            ->assertSee('02746270228')
            ->assertSee('Via Cattani 11');
    }

    public function test_submitting_the_form_stores_the_message_and_confirms(): void
    {
        Livewire::test(Contact::class)
            ->set('form.firstName', 'Giulia')
            ->set('form.lastName', 'Rossi')
            ->set('form.email', 'giulia@example.com')
            ->set('form.reason', 'Informazioni generali')
            ->set('form.message', 'Vorrei sapere di più sulle smartbox.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showConfirmation', true);

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame('giulia@example.com', ContactMessage::first()->email);
    }

    public function test_the_form_requires_every_field(): void
    {
        Livewire::test(Contact::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.lastName', 'form.email', 'form.reason', 'form.message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_closing_the_confirmation_resets_the_form(): void
    {
        Livewire::test(Contact::class)
            ->set('form.firstName', 'Giulia')
            ->set('form.lastName', 'Rossi')
            ->set('form.email', 'giulia@example.com')
            ->set('form.reason', 'Altro')
            ->set('form.message', 'Ciao!')
            ->call('submit')
            ->call('closeConfirmation')
            ->assertSet('showConfirmation', false)
            ->assertSet('form.firstName', '');
    }

    public function test_the_hero_embeds_the_hq_map_when_the_key_is_configured(): void
    {
        config(['services.google.maps_key' => 'test-key']);

        $this->get('/contattaci')
            ->assertOk()
            ->assertSee('google.com/maps/embed/v1/place', false)
            ->assertSee(urlencode('Via Cattani 11'), false);
    }

    public function test_the_footer_links_to_the_contact_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('contact'));
    }
}
