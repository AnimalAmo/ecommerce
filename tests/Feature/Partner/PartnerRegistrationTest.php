<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_1_page_renders(): void
    {
        $this->get(route('partner.register'))
            ->assertOk()
            ->assertSee(__('partner.register.heading'))
            ->assertSee(__('partner.register.step'))
            ->assertSee(__('partner.register.next'));
    }

    public function test_step_1_requires_the_mandatory_fields(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.email', 'form.vat', 'form.pec', 'form.sdi'])
            ->assertNoRedirect();
    }

    public function test_step_1_rejects_a_non_numeric_cap(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.zip', 'abc')
            ->call('submit')
            ->assertHasErrors(['form.zip']);
    }

    public function test_step_1_advances_to_step_2_when_valid(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->set('form.pec', 'pethotel@pec.it')
            ->set('form.sdi', 'ABCDEF1')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.register.step2'));
    }

    /**
     * Regressione: i campi erano composti a mano (flux:field + flux:label +
     * flux:input) e Flux inietta lo slot d'errore solo quando `label` è una
     * PROP del controllo. Gli errori finivano nell'error bag ma non in pagina:
     * un submit rifiutato ridisegnava il form identico e "Prosegui" sembrava
     * un tasto morto. Non basta assertHasErrors — il messaggio deve USCIRE.
     */
    public function test_step_1_shows_the_error_of_the_only_field_left_out(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->set('form.pec', 'pethotel@pec.it')
            ->set('form.sdi', 'ABCDEF1')
            // La provincia è l'unica non scelta: è il caso segnalato dalla cliente.
            ->call('submit')
            ->assertHasErrors('form.province')
            ->assertNoRedirect()
            ->assertSee('Inserisci la provincia.');
    }

    /** Ogni campo deve avere il suo slot: un solo buco riapre il tasto morto. */
    public function test_step_1_shows_a_message_for_every_field(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertSee([
                'Inserisci il nome.',
                'Inserisci il cognome.',
                'Inserisci la ragione sociale.',
                'Inserisci l\'email.',
                'Inserisci l\'indirizzo.',
                'Inserisci la provincia.',
                'Inserisci il CAP.',
                'Inserisci il numero di cellulare.',
                'Inserisci la partita IVA.',
                'Inserisci il codice fiscale.',
                'Inserisci la PEC.',
                'Inserisci il codice SDI.',
            ]);
    }

    /** I messaggi dei dati fiscali dicono il limite, non "Valore troppo lungo." */
    public function test_step_1_explains_the_length_of_the_tax_fields(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.zip', '351')
            ->set('form.vat', 'PARTITA IVA 12345678901')
            ->set('form.sdi', 'nonhosdi')
            ->call('submit')
            ->assertSee([
                'Il CAP deve avere 5 cifre.',
                'La partita IVA non può superare i 13 caratteri.',
                'Il codice SDI è di 7 caratteri.',
            ]);
    }

    public function test_step_2_page_renders_the_three_service_options(): void
    {
        $this->get(route('partner.register.step2'))
            ->assertOk()
            ->assertSee(__('partner.register2.step'))
            ->assertSee(__('partner.register2.struttura_title'))
            ->assertSee(__('partner.register2.attivita_title'))
            ->assertSee(__('partner.register2.servizi_title'))
            ->assertSee(__('partner.register2.submit'));
    }

    public function test_step_2_requires_a_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_rejects_an_unknown_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'qualcosaltro')
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_accepts_a_valid_selection(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->call('createAccount')
            ->assertHasNoErrors();
    }
}
