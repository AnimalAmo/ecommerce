<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfileInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfileInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_personal_and_fiscal_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.info_heading'))
            ->assertSee(__('partner.profile.business_name'))
            ->assertSee(__('partner.profile.vat'))
            ->assertDontSee('PEC')
            ->assertDontSee('SDI')
            ->assertSee(__('partner.profile.save'));
    }

    public function test_it_rehydrates_the_partner_data(): void
    {
        $partner = $this->actingAsActivePartner(['first_name' => 'Susanna', 'last_name' => 'Rossi']);
        $partner->partnerProfile()->create(['business_name' => 'Hotel Rosovino', 'vat' => '86334519757']);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.firstName', 'Susanna')
            ->assertSet('form.businessName', 'Hotel Rosovino')
            ->assertSet('form.vat', '86334519757');
    }

    public function test_save_requires_the_mandatory_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', '')
            ->set('form.businessName', '')
            ->set('form.vat', '')
            ->call('save')
            ->assertHasErrors(['form.firstName', 'form.businessName', 'form.vat']);
    }

    public function test_save_rejects_an_email_already_in_use(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.email', 'taken@example.com')
            ->call('save')
            ->assertHasErrors(['form.email']);
    }

    public function test_save_persists_personal_and_fiscal_data(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.city', 'Padova')
            ->set('form.zip', '35100')
            ->set('form.vat', '12345678901')
            ->set('form.phone', '3331234567')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $partner->id,
            'first_name' => 'Mario',
            'email' => 'mario@example.com',
        ]);
        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'business_name' => 'Pet Hotel Srl',
            'vat' => '12345678901',
            'city' => 'Padova',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Orari di apertura o disponibilità (risposta della cliente, 27/09/2026)
    |--------------------------------------------------------------------------
    | Sono sul profilo e non sulla bozza perché la cliente li nomina in due
    | punti — fra i recapiti pubblici e fra i campi dell'attività — e due campi
    | che possono contraddirsi sono peggio di uno. Le schede attività li leggono
    | da qui (ActivityDetail::openingHours).
    */

    /** I campi ci sono, con il loro avviso: sono facoltativi e si vedono a catalogo. */
    private function fillRequired(Testable $component): Testable
    {
        return $component
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.city', 'Padova')
            ->set('form.zip', '35100')
            ->set('form.vat', '12345678901')
            ->set('form.phone', '3331234567')
            ->set('form.taxCode', 'RSSMRA80A01H501U');
    }

    public function test_page_renders_the_opening_hours_field(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.opening_hours'))
            ->assertSee(__('partner.profile.opening_hours_hint'));
    }

    public function test_save_persists_the_opening_hours_in_both_languages(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', 'Lun-Ven 9-18')
            ->set('form.openingHours.en', 'Mon-Fri 9-18')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('Lun-Ven 9-18', $profile->getTranslation('opening_hours', 'it'));
        $this->assertSame('Mon-Fri 9-18', $profile->getTranslation('opening_hours', 'en'));
    }

    public function test_it_rehydrates_the_saved_opening_hours(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.openingHours.it', 'Lun-Sab 8-20')
            ->assertSet('form.openingHours.en', 'Mon-Sat 8-20');
    }

    /** «Eventuali»: un profilo salvato senza orari resta valido e la colonna vuota. */
    public function test_the_opening_hours_are_optional(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], $partner->refresh()->partnerProfile->getTranslations('opening_hours'));
    }

    /** 200 caratteri come gli altri testi liberi localizzati del partner; 201 no. */
    public function test_opening_hours_longer_than_the_limit_are_refused(): void
    {
        $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', str_repeat('a', 201))
            ->call('save')
            ->assertHasErrors(['form.openingHours.it' => 'max']);
    }

    // ── Giro del tester, 28/09/2026: W5 sugli orari del profilo ──────────────
    //
    // `toProfile()` va a `updateOrCreate()`, che fonde sul profilo esistente:
    // con `array_filter` gli orari inglesi salvati una volta restavano per
    // sempre sulla scheda /en. La scheda li legge dal profilo, non da una copia.

    public function test_svuotare_gli_orari_inglesi_li_toglie_e_litaliano_resta(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->assertSet('form.openingHours.en', 'Mon-Sat 8-20')
            ->set('form.openingHours.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame(['it' => 'Lun-Sab 8-20'], $profile->getTranslations('opening_hours'));
        $this->assertSame('Lun-Sab 8-20', $profile->getTranslation('opening_hours', 'en'));
    }

    public function test_svuotare_gli_orari_in_tutte_le_lingue_li_toglie(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', '')
            ->set('form.openingHours.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], $partner->refresh()->partnerProfile->getTranslations('opening_hours'));
    }
}
