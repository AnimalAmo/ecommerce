<?php

namespace Tests\Feature\Admin\Catalog;

use App\Exceptions\PartnerServiceException;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Il pannello è solo in italiano: il messaggio non deve dipendere dal locale del momento. */
class PartnerServiceExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_names_the_business_when_there_is_a_profile(): void
    {
        app()->setLocale('it');

        $partner = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        PartnerProfile::factory()->for($partner)->create(['business_name' => 'Cascina Bau']);

        $exception = PartnerServiceException::notEligible($partner->fresh());

        $this->assertStringContainsString('Cascina Bau', $exception->getMessage());
        $this->assertSame($partner->id, $exception->partnerId);
    }

    public function test_it_falls_back_to_the_person_when_the_profile_is_missing(): void
    {
        $partner = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);

        $this->assertStringContainsString('Anna Verdi', PartnerServiceException::notEligible($partner)->getMessage());
    }

    public function test_the_message_stays_italian_with_an_english_locale(): void
    {
        app()->setLocale('en');

        $partner = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);

        $this->assertStringContainsString('non può ricevere schede', PartnerServiceException::notEligible($partner)->getMessage());
    }

    /** Il messaggio col nome sostituito è testato QUI, non nelle pagine: nei componenti esce come toast Flux, che vive in un popover fuori dal DOM della risposta. */
    public function test_the_placeholder_is_replaced(): void
    {
        $partner = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);

        $this->assertStringNotContainsString(':name', PartnerServiceException::notEligible($partner)->getMessage());
    }
}
