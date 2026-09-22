<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\ActivityCreate;
use App\Models\Partner\PartnerProfile;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityCreatePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class]);
    }

    public function test_the_form_opens_for_the_chosen_partner(): void
    {
        $partner = $this->actingAsPayablePartner();
        $partner->partnerProfile->update(['business_name' => 'Zampe in Viaggio']);
        $this->actingAsSuperadmin();

        $this->get(route('admin.catalog.create', ['family' => 'activity', 'partner' => $partner->id]))
            ->assertOk()
            ->assertSee('Nuova attivit')
            ->assertSee('Zampe in Viaggio')
            ->assertSee('Informazioni generali')
            ->assertSee('Servizi dedicati agli animali')
            // Il riquadro partner esiste davvero (partial condiviso del Task 4).
            // Il testo è quello che il partial stampa: «Stripe: Collegato e
            // pagabile». La stringa «Stripe collegato» non esiste in pagina.
            ->assertSee(__('admin-catalog.create.stripe_status.payable'))
            ->assertDontSee('admin-catalog.create.');
    }

    public function test_switching_to_activity_clears_the_event_times(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        Livewire::withQueryParams(['partner' => $partner->id])
            ->test(ActivityCreate::class)
            ->set('type', 'eventi')
            ->set('info.timeStart', '10:00')
            ->set('info.timeEnd', '18:00')
            ->assertSet('info.isEvent', true)
            ->set('type', 'attivita')
            ->assertSet('info.isEvent', false)
            ->assertSet('info.timeStart', '')
            ->assertSet('info.timeEnd', '');
    }

    public function test_the_detailed_description_is_required_only_for_activities(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        Livewire::withQueryParams(['partner' => $partner->id])
            ->test(ActivityCreate::class)
            ->set('type', 'attivita')
            ->call('save')
            ->assertHasErrors(['detailedDescription.it' => 'required']);

        Livewire::withQueryParams(['partner' => $partner->id])
            ->test(ActivityCreate::class)
            ->set('type', 'eventi')
            ->call('save')
            ->assertHasNoErrors('detailedDescription.it')
            ->assertHasErrors(['info.timeStart' => 'required']);
    }

    /** Senza partner in query string non si salva: l'errore è sul select, non un 500. */
    public function test_without_a_partner_nothing_is_saved(): void
    {
        $this->actingAsSuperadmin();

        Livewire::test(ActivityCreate::class)
            ->call('save')
            ->assertHasErrors('partnerChoice');
    }

    public function test_an_online_partner_without_stripe_is_announced_before_saving(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create(['business_name' => 'Cani per Caso']);
        $this->actingAsSuperadmin();

        $this->get(route('admin.catalog.create', ['family' => 'activity', 'partner' => $partner->id]))
            ->assertOk()
            ->assertSee('Cani per Caso')
            // Avviso preventivo, SENZA segnaposto: `awaiting_stripe` contiene
            // `:name` e un assertSee su quella chiave non troverebbe mai un match.
            ->assertSee(__('admin-catalog.create.stripe_missing_notice'));
    }
}
