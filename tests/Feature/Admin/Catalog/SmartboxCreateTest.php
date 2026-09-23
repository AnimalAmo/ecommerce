<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\SmartboxCreate;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SmartboxCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('public');
        $this->seed(AmenitySeeder::class);
    }

    private function componentFor(User $partner): Testable
    {
        return Livewire::withQueryParams(['partner' => $partner->id])->test(SmartboxCreate::class);
    }

    /** Gli stessi dati della fixture di SmartboxPublisherTest, dal lato pannello. */
    private function fill(Testable $component): Testable
    {
        return $component
            ->set('type', 'benessere')
            ->set('name.it', 'Weekend Zen col tuo cane')
            ->set('name.en', 'Zen weekend with your dog')
            ->set('description.it', 'Relax e coccole per entrambi.')
            ->set('detailedDescription.it', 'Due giorni di spa pet friendly.')
            ->set('detailedDescription.en', 'Two days of pet friendly spa.')
            ->set('durationDays', 3)
            ->set('when', '15')
            ->set('meals.meals', ['colazione', 'cena'])
            ->set('meals.mealTimes.colazione.from', '08:00')
            ->set('meals.mealTimes.colazione.to', '10:30')
            ->set('meals.mealTimes.cena.from', '19:30')
            ->set('meals.mealTimes.cena.to', '21:30')
            ->set('included', ['wifi', 'aria_condizionata'])
            ->set('additional', ['spa'])
            ->set('animalServices', ['omaggio'])
            ->set('price', '215')
            ->set('photos', [
                UploadedFile::fake()->image('uno.jpg'),
                UploadedFile::fake()->image('due.jpg'),
                UploadedFile::fake()->image('tre.jpg'),
                UploadedFile::fake()->image('quattro.jpg'),
            ]);
    }

    private function wizardDraft(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'benessere',
            'name' => ['it' => 'Weekend Zen col tuo cane', 'en' => 'Zen weekend with your dog'],
            'description' => ['it' => 'Relax e coccole per entrambi.'],
            'detailed_description' => ['it' => 'Due giorni di spa pet friendly.', 'en' => 'Two days of pet friendly spa.'],
            'duration_days' => 3,
            'cancellation_when' => '15',
            'meals' => ['colazione', 'cena'],
            'meal_times' => [
                'colazione' => ['from' => '08:00', 'to' => '10:30'],
                'pranzo' => ['from' => '', 'to' => ''],
                'cena' => ['from' => '19:30', 'to' => '21:30'],
            ],
            'included_services' => ['wifi', 'aria_condizionata'],
            'additional_services' => ['spa'],
            'animal_services' => ['omaggio'],
            'photos' => ['smartbox-photos/zen.jpg'],
            'price' => '215',
        ]);
    }

    private function comparable(SmartboxPackage $package): array
    {
        $package = $package->fresh('amenities');

        return [
            'type' => $package->type->value,
            'title' => $package->getTranslations('title'),
            'description' => $package->getTranslations('description'),
            'extended_description' => $package->getTranslations('extended_description'),
            'audience' => $package->audience,
            'price_cents' => $package->price_cents,
            'price_from_cents' => $package->price_from_cents,
            'validity_months' => $package->validity_months,
            'features' => $package->features,
            'general_info' => $package->general_info,
            'cancellation_policy_days' => $package->cancellation_policy_days,
            'hotel' => $package->amenityRows('hotel'),
            'animal' => $package->amenityRows('animal'),
        ];
    }

    public function test_the_published_package_is_the_one_the_wizard_would_produce(): void
    {
        $adminPartner = $this->actingAsPayablePartner();
        $wizardPartner = $this->actingAsPayablePartner();
        $reference = app(DraftPublisher::class)->publish($this->wizardDraft($wizardPartner));

        $this->actingAsSuperadmin();
        $this->fill($this->componentFor($adminPartner))->call('save')->assertHasNoErrors();

        $package = SmartboxPackage::withHidden()->where('user_id', $adminPartner->id)->sole();

        $this->assertSame($this->comparable($reference), $this->comparable($package));
    }

    public function test_the_draft_belongs_to_the_partner_and_carries_the_family(): void
    {
        $partner = $this->actingAsPayablePartner();
        $admin = $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->assertSame($partner->id, $draft->user_id);
        $this->assertNotSame($admin->id, $draft->user_id);
        $this->assertSame('smartbox', $draft->service_category);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->status);
        $this->assertSame(12, $draft->current_step);
        $this->assertStringStartsWith('smartbox-photos/', $draft->photos[0]);
    }

    public function test_none_excludes_the_meals_and_the_meals_exclude_none(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->componentFor($partner)
            ->set('meals.meals', ['colazione'])
            ->set('meals.meals', ['colazione', 'nessuno'])
            ->assertSet('meals.meals', ['nessuno'])
            ->set('meals.meals', ['nessuno', 'cena'])
            ->assertSet('meals.meals', ['cena']);
    }

    public function test_only_the_chosen_partner_structures_can_be_included(): void
    {
        $partner = $this->actingAsPayablePartner();
        $other = $this->actingAsPayablePartner();

        $mine = Structure::factory()->create(['user_id' => $partner->id, 'name' => ['it' => 'Hotel Bau']]);
        $his = Structure::factory()->create(['user_id' => $other->id, 'name' => ['it' => 'Hotel Miao']]);
        Structure::factory()->create(['user_id' => null, 'name' => ['it' => 'Hotel Finto']]);

        $this->actingAsSuperadmin();

        $this->componentFor($partner)
            ->assertSee('Hotel Bau')
            ->assertDontSee('Hotel Miao')
            ->assertDontSee('Hotel Finto');

        $this->fill($this->componentFor($partner))
            ->set('structures', [(string) $his->id])
            ->call('save')
            ->assertHasErrors('structures.0');

        $this->assertSame(0, StructureDraft::count());

        $this->fill($this->componentFor($partner))
            ->set('structures', [(string) $mine->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([(string) $mine->id], StructureDraft::sole()->smartbox_structures);
    }

    /**
     * Il cambio partner passa da `partnerChoice`, che è la proprietà pubblica
     * del select: `$partnerId` è #[Locked] e un `set('partnerId', …)` darebbe
     * CannotUpdateLockedPropertyException senza mai arrivare al reset.
     */
    public function test_changing_partner_clears_the_included_structures(): void
    {
        $partner = $this->actingAsPayablePartner();
        $other = $this->actingAsPayablePartner();
        $mine = Structure::factory()->create(['user_id' => $partner->id, 'name' => ['it' => 'Hotel Bau']]);
        $this->actingAsSuperadmin();

        $this->componentFor($partner)
            ->set('structures', [(string) $mine->id])
            ->set('partnerChoice', (string) $other->id)
            ->assertSet('partnerId', $other->id)
            ->assertSet('structures', [])
            ->assertDontSee('Hotel Bau');
    }

    public function test_the_italian_comma_is_accepted_in_the_price(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('price', '99,90')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(9990, SmartboxPackage::withHidden()->sole()->price_cents);
    }

    public function test_the_other_animal_service_is_stored_as_an_italian_translation(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('animalServices', ['omaggio', 'altro'])
            ->set('animalOther', 'Toelettatura inclusa')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            ['it' => 'Toelettatura inclusa'],
            StructureDraft::sole()->getTranslations('animal_services_other'),
        );
    }

    public function test_with_moderation_on_the_row_is_born_approved(): void
    {
        config(['admin.moderation' => true]);

        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $package = SmartboxPackage::withHidden()->sole();

        $this->assertSame('approved', $package->approval_status);
        $this->assertNotNull($package->approved_at);
        $this->assertNull($package->approval_requested_at);
    }

    public function test_an_online_partner_without_stripe_leaves_the_draft_waiting(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->assertSame(0, SmartboxPackage::withHidden()->count());
        $this->assertNotNull($draft->publish_requested_at);
        $this->assertEqualsCanonicalizing($draft->photos, Storage::disk('public')->files('smartbox-photos'));
    }

    public function test_a_name_longer_than_the_limit_is_refused(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('name.it', str_repeat('a', 111))
            ->call('save')
            ->assertHasErrors(['name.it' => 'max']);

        $this->assertSame(0, StructureDraft::count());
    }

    /** I 200 caratteri del wizard restano su descrizione e descrizione dettagliata. */
    public function test_a_two_hundred_character_detailed_description_is_accepted(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('detailedDescription.it', str_repeat('a', 200))
            ->call('save')
            ->assertHasNoErrors('detailedDescription.it');
    }

    public function test_fewer_than_four_photos_leave_nothing_behind(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            // `set` con dei file passa da _finishUpload, che in questa versione
            // di Livewire ACCODA invece di sostituire (stessa nota in
            // StructureCreateTest): senza svuotare prima, alle quattro foto di
            // fill() se ne aggiungerebbe una quinta e il cofanetto verrebbe
            // pubblicato. L'array vuoto non è un upload, quindi quello sostituisce.
            ->set('photos', [])
            ->set('photos', [UploadedFile::fake()->image('sola.jpg')])
            ->call('save')
            ->assertHasErrors('photos')
            // Stesso testo delle altre due famiglie: photoMinError() non è
            // sovrascritto da nessuna parte.
            ->assertSee(__('admin-catalog.create.photos_min'));

        $this->assertSame(0, StructureDraft::count());
        $this->assertSame([], Storage::disk('public')->files('smartbox-photos'));
    }

    public function test_the_partner_finds_the_package_and_reopens_his_own_wizard(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->actingAs($partner);
        Livewire::test(PartnerMyServices::class)
            ->assertSee('Weekend Zen col tuo cane')
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.smartbox.type'));
    }

    public function test_only_a_superadmin_reaches_the_form(): void
    {
        $partner = $this->actingAsPayablePartner();
        $url = route('admin.catalog.create', ['family' => 'smartbox', 'partner' => $partner->id]);

        $this->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect(route('admin.login'));
    }

    /**
     * Togliendo "altro" la textarea sparisce dalla pagina, ma il testo resta
     * nella proprietà e finiva comunque sulla bozza: un residuo che l'admin
     * non vede più e non può più cancellare.
     */
    public function test_deselecting_the_other_animal_service_clears_its_free_text(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('animalServices', ['omaggio', 'altro'])
            ->set('animalOther', 'Toelettatura inclusa')
            ->set('animalServices', ['omaggio'])
            ->assertSet('animalOther', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], StructureDraft::sole()->getTranslations('animal_services_other'));
    }
}
