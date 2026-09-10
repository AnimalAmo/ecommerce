<?php

namespace Tests\Feature\Partner;

use App\Models\Partner\PartnerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Anagrafica Connect del partner: i due flag arrivano dal webhook
 * account.updated e decidono se può vendere e se può essere pagato. Le due
 * colonne di provvigione sono una deroga, non la regola: nulle = config.
 */
class PartnerStripeAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_partner_senza_account_stripe_non_puo_vendere(): void
    {
        $profile = PartnerProfile::factory()->create(['stripe_account_id' => null]);

        $this->assertFalse($profile->canSell());
        $this->assertFalse($profile->canBePaid());
    }

    public function test_servono_charges_enabled_per_vendere_e_payouts_enabled_per_essere_pagati(): void
    {
        $profile = PartnerProfile::factory()->create([
            'stripe_account_id' => 'acct_test',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => false,
        ]);

        $this->assertTrue($profile->canSell());
        $this->assertFalse($profile->canBePaid());
    }

    public function test_un_account_completo_puo_vendere_ed_essere_pagato(): void
    {
        $profile = PartnerProfile::factory()->connected()->create();

        $this->assertTrue($profile->canSell());
        $this->assertTrue($profile->canBePaid());
    }

    public function test_senza_deroga_valgono_i_valori_di_configurazione(): void
    {
        $profile = PartnerProfile::factory()->create([
            'commission_rate_bp' => null,
            'commission_min_cents' => null,
        ]);

        $this->assertSame(1000, $profile->commissionRateBp());
        $this->assertSame(5000, $profile->commissionMinCents());
    }

    public function test_la_deroga_del_partner_vince_sulla_configurazione(): void
    {
        $profile = PartnerProfile::factory()->create([
            'commission_rate_bp' => 800,
            'commission_min_cents' => 0,
        ]);

        $this->assertSame(800, $profile->commissionRateBp());
        // Zero è una deroga vera (nessuna franchigia), non un valore assente.
        $this->assertSame(0, $profile->commissionMinCents());
    }

    public function test_i_requisiti_pendenti_sono_una_lista(): void
    {
        $profile = PartnerProfile::factory()->create([
            'stripe_requirements_due' => ['individual.verification.document'],
        ]);

        $this->assertSame(['individual.verification.document'], $profile->fresh()->stripe_requirements_due);
    }
}
