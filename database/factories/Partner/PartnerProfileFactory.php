<?php

namespace Database\Factories\Partner;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProfile>
 */
class PartnerProfileFactory extends Factory
{
    protected $model = PartnerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'vat' => fake()->numerify('###########'),
            'tax_code' => fake()->bothify('??????##?##?###?'),
            'pec' => fake()->unique()->safeEmail(),
            'sdi' => fake()->bothify('???####'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->lexify('??'),
            'zip' => fake()->numerify('#####'),
            // Onboarding Stripe non ancora avviato: è lo stato in cui nasce
            // un partner, e quello in cui non può né vendere né essere pagato.
            'stripe_account_id' => null,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
            'stripe_requirements_due' => null,
            'commission_rate_bp' => null,
            'commission_min_cents' => null,
        ];
    }

    /** Onboarding completato: incassa e riceve bonifici. */
    public function connected(): static
    {
        return $this->state(fn (): array => [
            'stripe_account_id' => 'acct_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
            'stripe_requirements_due' => [],
        ]);
    }
}
