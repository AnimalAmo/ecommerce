<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\CatalogIndex;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Admin\Catalog\AdminServiceCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Da dove si entra nella creazione di una scheda: il menù sulla scheda del
 * partner e il bottone del catalogo. Sono due strade per la stessa rotta, e
 * tutte e due devono offrire solo partner che AdminServiceCreator accetta —
 * un nome proposto e poi rifiutato al salvataggio è peggio di un nome assente.
 */
class CatalogCreateEntryPointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->actingAsSuperadmin();
    }

    private function partner(array $attributes = []): User
    {
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $partner->assignRole('partner');
        PartnerProfile::factory()->connected()->for($partner)->create(['business_name' => 'Cascina Bau']);

        return $partner->fresh();
    }

    public function test_the_partner_card_offers_the_four_families(): void
    {
        $partner = $this->partner();

        $response = $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('Crea scheda');

        foreach (AdminServiceCreator::CREATABLE_FAMILIES as $family) {
            $response->assertSee(route('admin.catalog.create', ['family' => $family, 'partner' => $partner->id]), false);
        }
    }

    /** P2 ha già uno <x-slot:aside> su questa card: il dropdown ci va DENTRO, non accanto. */
    public function test_the_partner_card_keeps_the_resend_link_button(): void
    {
        $partner = $this->partner();

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee(__('admin-people.users.resend_welcome'))
            ->assertSee('Crea scheda');
    }

    public function test_a_customer_has_no_create_menu(): void
    {
        $client = User::factory()->create(['is_active' => true]);

        $this->get(route('admin.users.show', $client))->assertOk()->assertDontSee('Crea scheda');
    }

    public function test_a_deactivated_partner_has_no_create_menu(): void
    {
        $this->get(route('admin.users.show', $this->partner(['is_active' => false])))
            ->assertOk()
            ->assertDontSee('Crea scheda');
    }

    public function test_an_anonymized_partner_has_no_create_menu(): void
    {
        $this->get(route('admin.users.show', $this->partner(['anonymized_at' => now()])))
            ->assertOk()
            ->assertDontSee('Crea scheda');
    }

    public function test_the_catalog_header_offers_a_new_listing(): void
    {
        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('Nuova scheda')
            ->assertDontSee('admin-catalog.create.');
    }

    public function test_the_modal_lists_only_eligible_partners(): void
    {
        $this->partner();

        Role::findOrCreate('partner', 'web');
        $inactive = User::factory()->create(['is_active' => false, 'last_name' => 'Spenta']);
        $inactive->assignRole('partner');
        PartnerProfile::factory()->connected()->for($inactive)->create(['business_name' => 'Locanda Spenta']);

        Livewire::test(CatalogIndex::class)
            ->assertSee('Cascina Bau')
            ->assertDontSee('Locanda Spenta');
    }

    public function test_the_modal_leads_to_the_chosen_family_for_the_chosen_partner(): void
    {
        $partner = $this->partner();

        Livewire::test(CatalogIndex::class)
            ->set('newPartner', (string) $partner->id)
            ->set('newFamily', 'smartbox')
            ->call('startCreate')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.catalog.create', ['family' => 'smartbox', 'partner' => $partner->id]));
    }

    public function test_the_modal_refuses_to_continue_without_a_partner(): void
    {
        Livewire::test(CatalogIndex::class)
            ->set('newFamily', 'activity')
            ->call('startCreate')
            ->assertHasErrors('newPartner')
            ->assertNoRedirect();
    }

    public function test_the_modal_refuses_a_partner_who_is_not_eligible(): void
    {
        Role::findOrCreate('partner', 'web');
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('partner');
        PartnerProfile::factory()->connected()->for($inactive)->create();

        Livewire::test(CatalogIndex::class)
            ->set('newPartner', (string) $inactive->id)
            ->call('startCreate')
            ->assertHasErrors('newPartner')
            ->assertNoRedirect();
    }

    public function test_the_modal_refuses_an_unknown_family(): void
    {
        Livewire::test(CatalogIndex::class)
            ->set('newPartner', (string) $this->partner()->id)
            ->set('newFamily', 'pacchetti')
            ->call('startCreate')
            ->assertHasErrors('newFamily')
            ->assertNoRedirect();
    }

    /**
     * Il menù era acceso dal solo profilo aziendale, mentre
     * `AdminServiceCreator` pretende anche il ruolo `partner`: il link si
     * apriva e la pagina scartava il partner in silenzio, lasciando l'admin
     * davanti a un form vuoto senza spiegazioni.
     */
    public function test_a_user_with_a_profile_but_no_partner_role_has_no_create_menu(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        PartnerProfile::factory()->connected()->for($user)->create(['business_name' => 'Senza Ruolo']);

        $this->get(route('admin.users.show', $user))->assertOk()->assertDontSee('Crea scheda');
    }

    /**
     * Il campo di ricerca dentro un `flux:select variant="listbox" searchable`
     * lo disegna Flux, con `__('Search...')`: senza `lang/it.json` la stringa
     * resta inglese in mezzo a un pannello italiano. Lo si vede solo a
     * browser, perché è un placeholder dentro il markup della modale.
     */
    public function test_the_partner_search_box_is_in_italian(): void
    {
        $this->partner();

        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('Cerca…')
            ->assertDontSee('Search...');
    }
}
