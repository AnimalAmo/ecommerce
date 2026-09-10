<?php

namespace Database\Factories;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Esplicito, non lasciato al default della colonna: il modello
            // restituito da create() non lo rileggerebbe, e i controlli
            // `! $user->is_active` vedrebbero null (= disattivato).
            'is_active' => true,
        ];
    }

    /** Account sospeso: legge/scrive come cliente ma non entra nell'area partner. */
    /**
     * Partner con onboarding Stripe completato: può incassare ed essere
     * bonificato, quindi i suoi servizi possono andare a catalogo.
     */
    public function stripeConnected(): static
    {
        return $this->afterCreating(function (User $user): void {
            PartnerProfile::factory()->connected()->for($user)->create();
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
