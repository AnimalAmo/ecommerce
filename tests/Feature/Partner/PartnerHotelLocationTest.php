<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelLocation;
use App\Models\Structure\StructureDraft;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelLocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le province servono a database: `HotelLocationForm` verifica la sigla con
     * `exists:provinces,short_name`, quindi senza il seeder anche 'PD' sarebbe
     * rifiutata e il test proverebbe il contrario di quel che dice. Stessa
     * coppia di seeder di StructurePublisherTest (ProvinceSeeder ha bisogno
     * delle regioni per `region_id`).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RegionSeeder::class, ProvinceSeeder::class]);
    }

    public function test_page_renders_the_location_fields(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.location'))
            ->assertOk()
            ->assertSee(__('partner.hotel_location.heading'))
            ->assertSee(__('partner.hotel_location.step'))
            ->assertSee(__('partner.hotel_location.address'))
            ->assertSee(__('partner.hotel_location.city'))
            ->assertSee(__('partner.hotel_location.license'))
            ->assertSee(__('partner.hotel_location.next'));
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(HotelLocation::class)
            ->call('next')
            ->assertHasErrors(['form.address', 'form.city', 'form.province', 'form.zip', 'form.license']);
    }

    public function test_next_rejects_a_non_numeric_zip(): void
    {
        Livewire::test(HotelLocation::class)
            ->set('form.zip', 'abc')
            ->call('next')
            ->assertHasErrors('form.zip');
    }

    public function test_next_accepts_valid_data(): void
    {
        Livewire::test(HotelLocation::class)
            ->set('form.address', 'Via Roma 1')
            ->set('form.city', 'Padova')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.license', 'LIC-12345')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.description'));

        $this->assertDatabaseHas('structure_drafts', ['address' => 'Via Roma 1', 'city' => 'Padova', 'current_step' => 3]);
    }

    /**
     * Sigla fuori elenco: la select ricercabile del wizard manda sempre una
     * sigla del database, ma la richiesta Livewire è del client. Una sigla
     * inventata arrivava fino alla bozza e la pubblicazione ne ricavava
     * `region_id` NULL, cioè una scheda invisibile su ogni pagina regione.
     * Stessa coppia di casi del pannello admin (StructureCreateTest): sigla
     * inventata → `exists`, sigla vuota → `required`.
     */
    public function test_next_rejects_a_province_outside_the_list(): void
    {
        Livewire::test(HotelLocation::class)
            ->set('form.address', 'Via Roma 1')
            ->set('form.city', 'Padova')
            ->set('form.province', 'ZZ')
            ->set('form.zip', '35100')
            ->set('form.license', 'LIC-12345')
            ->call('next')
            ->assertHasErrors(['form.province' => 'exists']);

        // `mount()` crea sempre la bozza vuota della sessione: quel che conta è
        // che lo step non abbia scritto la sigla inventata.
        $this->assertNull(StructureDraft::first()->province);
    }

    public function test_it_rehydrates_the_saved_location(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 3, 'city' => 'Verona']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelLocation::class)->assertSet('form.city', 'Verona');
    }
}
