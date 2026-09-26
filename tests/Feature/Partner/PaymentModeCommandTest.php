<?php

namespace Tests\Feature\Partner;

use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `animalamo:payment-mode` esiste per un caso vero: un partner in produzione
 * («Bio Boutique Laurino», 26/09/2026) è a incasso online ma non ha mai
 * collegato Stripe, quindi le sue schede portano il cliente a un checkout che
 * non può funzionare. Va rimesso a pagamento diretto.
 *
 * Perché un comando e non una UPDATE a mano: la UPDATE salta
 * PartnerPaymentModeService — la validazione del link pubblico, il ripristino
 * delle bozze in attesa e il log di chi ha cambiato cosa. E soprattutto non
 * dice all'operatore quante schede sta spostando: il flag è del partner, non
 * della singola struttura.
 */
class PaymentModeCommandTest extends TestCase
{
    use RefreshDatabase;

    /** Il caso Laurino: incasso online dichiarato, nessun conto Stripe. */
    private function onlineWithoutStripe(array $attributes = []): PartnerProfile
    {
        return PartnerProfile::factory()->for(User::factory())->create([
            'business_name' => 'Bio Boutique Laurino',
            ...$attributes,
        ]);
    }

    public function test_it_switches_a_partner_to_direct_payment(): void
    {
        $profile = $this->onlineWithoutStripe();

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--offline' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFalse((bool) $profile->fresh()->online_payment);
    }

    public function test_it_counts_the_listings_the_switch_moves(): void
    {
        // Il flag è del partner: l'operatore deve vedere quante schede tocca
        // prima di confermare, non dopo.
        $profile = $this->onlineWithoutStripe();
        Structure::factory()->count(2)->create(['user_id' => $profile->user_id]);
        Event::factory()->create(['user_id' => $profile->user_id]);

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--offline' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('3 schede')
            ->assertSuccessful();
    }

    public function test_it_keeps_the_existing_payment_url_when_none_is_given(): void
    {
        // Trappola del service: set() riscrive payment_url con quello che
        // riceve, quindi passare null cancellerebbe il sito del partner.
        $profile = $this->onlineWithoutStripe(['payment_url' => 'https://laurino.example/prenota']);

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--offline' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('https://laurino.example/prenota', $profile->fresh()->payment_url);
    }

    public function test_it_stores_the_payment_url_when_given(): void
    {
        $profile = $this->onlineWithoutStripe();

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--offline' => true,
            '--url' => 'https://laurino.example/prenota',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('https://laurino.example/prenota', $profile->fresh()->payment_url);
    }

    public function test_it_refuses_an_unknown_partner(): void
    {
        $profile = $this->onlineWithoutStripe();

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Hotel che non esiste',
            '--offline' => true,
            '--force' => true,
        ])->assertFailed();

        $this->assertTrue((bool) $profile->fresh()->online_payment);
    }

    public function test_it_refuses_an_ambiguous_name_without_touching_anyone(): void
    {
        // In produzione due nomi possono somigliarsi: cambiare il partner
        // sbagliato gli spegne l'incasso senza che nessuno lo sappia.
        $laurino = $this->onlineWithoutStripe();
        $other = PartnerProfile::factory()->for(User::factory())->create([
            'business_name' => 'Bio Boutique Laurino Annex',
        ]);

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique',
            '--offline' => true,
            '--force' => true,
        ])->assertFailed();

        $this->assertTrue((bool) $laurino->fresh()->online_payment);
        $this->assertTrue((bool) $other->fresh()->online_payment);
    }

    public function test_it_finds_the_partner_by_email(): void
    {
        $user = User::factory()->create(['email' => 'laurino@example.test']);
        $profile = PartnerProfile::factory()->for($user)->create(['business_name' => 'Bio Boutique Laurino']);

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'laurino@example.test',
            '--offline' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFalse((bool) $profile->fresh()->online_payment);
    }

    public function test_it_needs_a_direction(): void
    {
        $profile = $this->onlineWithoutStripe();

        $this->artisan('animalamo:payment-mode', ['partner' => 'Bio Boutique Laurino'])->assertFailed();

        $this->assertTrue((bool) $profile->fresh()->online_payment);
    }

    public function test_it_refuses_to_go_online_without_stripe(): void
    {
        // La regola sta nel service e il comando non la scavalca: chi non è
        // pagabile non può tornare a incassare online.
        $profile = PartnerProfile::factory()->offline()->for(User::factory())->create([
            'business_name' => 'Bio Boutique Laurino',
        ]);

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--online' => true,
            '--force' => true,
        ])->assertFailed();

        $this->assertFalse((bool) $profile->fresh()->online_payment);
    }

    public function test_it_writes_nothing_when_the_confirmation_is_declined(): void
    {
        $profile = $this->onlineWithoutStripe();

        $this->artisan('animalamo:payment-mode', [
            'partner' => 'Bio Boutique Laurino',
            '--offline' => true,
        ])
            ->expectsConfirmation('Confermi il passaggio a pagamento diretto?', 'no')
            ->assertFailed();

        $this->assertTrue((bool) $profile->fresh()->online_payment);
    }
}
