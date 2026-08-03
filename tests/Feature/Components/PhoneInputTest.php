<?php

namespace Tests\Feature\Components;

use App\Livewire\Partner\Registration\WorkWithUs;
use App\Livewire\Profile\Profile;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Contratto di <x-phone-input>: il componente mostra prefisso e numero
 * separati, la property Livewire riceve l'E.164 e il DB lo conserva.
 */
class PhoneInputTest extends TestCase
{
    use RefreshDatabase;

    /** Payload profilo valido: il telefono è l'unico campo che varia nei test. */
    private const PROFILE = [
        'firstName' => 'Maria',
        'lastName' => 'Bianchi',
        'birthDate' => '01/12/1990',
        'email' => 'maria.bianchi@example.com',
        'petType' => 'Gatto',
        'address' => 'Via Roma 1',
        'city' => 'Torino',
        'zip' => '10121',
    ];

    public function test_renders_the_prefix_select_next_to_the_number(): void
    {
        $view = $this->blade(
            '<x-phone-input model="form.phone" :value="$value" />',
            ['value' => '+393331234567']
        );

        // Prefisso preselezionato dal numero e parte nazionale senza il +39.
        $view->assertSee('IT +39', false);
        $view->assertSee('333 123 4567', false);
        // La lista resta completa, non solo i mercati principali.
        $view->assertSee('HR +385', false);
    }

    public function test_hydrates_the_default_country_when_there_is_no_number(): void
    {
        $view = $this->blade('<x-phone-input model="form.phone" />');

        $view->assertSee('IT +39', false);
    }

    /** Il numero che arriva dal componente è già internazionale. */
    public function test_accepts_the_e164_value_produced_by_the_component(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::PROFILE, 'phone' => '+33612345678'])
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('+33612345678', $user->fresh()->phone);
    }

    public function test_rejects_a_number_that_is_not_a_real_phone(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::PROFILE, 'phone' => '+39000'])
            ->call('save')
            ->assertHasErrors('phone');

        $this->assertNotSame('+39000', $user->fresh()->phone);
    }

    public function test_rejects_a_prefix_that_does_not_match_the_number(): void
    {
        $user = User::factory()->create();

        // Cellulare italiano spedito col prefisso francese: non è un numero valido in Francia.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::PROFILE, 'phone' => '+333331234567'])
            ->call('save')
            ->assertHasErrors('phone');
    }

    /** Anche i form partner passano dallo stesso componente e dalle stesse regole. */
    public function test_partner_application_stores_the_number_in_e164(): void
    {
        Livewire::test(WorkWithUs::class)
            ->set('form.firstName', 'Susanna')
            ->set('form.lastName', 'Rossi')
            ->set('form.email', 'susanna@example.com')
            ->set('form.phone', '+393498798828')
            ->set('form.city', 'Milano')
            ->set('form.businessName', 'Hotel Rosovino')
            ->set('form.role', 'Titolare')
            ->set('form.offerType', 'Struttura ricettiva')
            ->set('form.description', 'Hotel pet friendly in centro a Milano.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('+393498798828', PartnerApplication::sole()->phone);
    }

    public function test_partner_application_rejects_an_invalid_number(): void
    {
        Livewire::test(WorkWithUs::class)
            ->set('form.phone', 'non lo so')
            ->call('submit')
            ->assertHasErrors('form.phone');

        $this->assertSame(0, PartnerApplication::count());
    }
}
