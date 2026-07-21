<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfilePayment;
use App\Models\User;
use App\Services\Payment\SavedPaymentMethodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfilePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_saved_card_the_page_opens_the_setup_form(): void
    {
        $user = User::factory()->create(['first_name' => 'Giulia', 'last_name' => 'Rossi']);

        $this->mockService(function (MockInterface $service): void {
            $service->shouldReceive('createSetupIntent')
                ->once()
                ->andReturn(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1']);
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->assertSet('editing', true)
            ->assertSet('clientSecret', 'seti_1_secret')
            ->assertSet('setupIntentId', 'seti_1')
            // Titolare precompilato col nome dell'utente.
            ->assertSet('cardHolder', 'Giulia Rossi');
    }

    public function test_with_a_saved_card_the_page_shows_it_without_opening_a_setup_intent(): void
    {
        $user = $this->userWithCard();

        $this->mockService(function (MockInterface $service): void {
            $service->shouldNotReceive('createSetupIntent');
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->assertSet('editing', false)
            ->assertSet('clientSecret', null)
            ->assertSee('•••• •••• •••• 4242')
            ->assertSee('12/30');
    }

    public function test_save_hands_the_confirmation_to_stripe_js(): void
    {
        $user = User::factory()->create();

        $this->mockService(function (MockInterface $service): void {
            $service->shouldReceive('createSetupIntent')
                ->andReturn(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1']);
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->call('markElementReady')
            ->set('cardHolder', 'Matteo Rossi')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saving', true)
            ->assertDispatched('confirm-setup');
    }

    public function test_save_requires_the_card_holder(): void
    {
        $user = User::factory()->create();

        $this->mockService(function (MockInterface $service): void {
            $service->shouldReceive('createSetupIntent')
                ->andReturn(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1']);
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->call('markElementReady')
            ->set('cardHolder', '')
            ->call('save')
            ->assertHasErrors('cardHolder')
            ->assertSet('saving', false)
            ->assertNotDispatched('confirm-setup');
    }

    public function test_a_confirmed_setup_intent_saves_the_card(): void
    {
        $user = User::factory()->create();

        $this->mockService(function (MockInterface $service) use ($user): void {
            $service->shouldReceive('createSetupIntent')
                ->andReturn(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1']);

            $service->shouldReceive('saveFromSetupIntent')
                ->once()
                ->withArgs(fn (User $saved, string $intentId) => $saved->is($user) && $intentId === 'seti_1')
                ->andReturnUsing(function () use ($user): bool {
                    $user->forceFill([
                        'stripe_payment_method_id' => 'pm_new',
                        'card_brand' => 'visa',
                        'card_last4' => '4242',
                        'card_exp_month' => 12,
                        'card_exp_year' => 2030,
                        'card_holder' => 'Matteo Rossi',
                    ])->save();

                    return true;
                });
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->call('markElementReady')
            ->set('cardHolder', 'Matteo Rossi')
            ->call('save')
            ->call('onSetupSucceeded', ['setup_intent_id' => 'seti_1'])
            ->assertSet('saving', false)
            ->assertSet('editing', false)
            ->assertSet('clientSecret', null)
            ->assertDispatched('toast-show');
    }

    public function test_a_setup_intent_id_from_another_session_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->mockService(function (MockInterface $service): void {
            $service->shouldReceive('createSetupIntent')
                ->andReturn(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1']);

            // Payload manomesso via devtools: nessun salvataggio.
            $service->shouldNotReceive('saveFromSetupIntent');
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->call('markElementReady')
            ->call('save')
            ->call('onSetupSucceeded', ['setup_intent_id' => 'seti_di_un_altro'])
            ->assertSet('saving', false)
            ->assertSet('editing', true);
    }

    public function test_removing_the_card_forgets_it_and_reopens_the_form(): void
    {
        $user = $this->userWithCard();

        $this->mockService(function (MockInterface $service) use ($user): void {
            $service->shouldReceive('forget')
                ->once()
                ->andReturnUsing(function () use ($user): void {
                    $user->forceFill([
                        'stripe_payment_method_id' => null,
                        'card_brand' => null,
                        'card_last4' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'card_holder' => null,
                    ])->save();
                });

            $service->shouldReceive('createSetupIntent')
                ->once()
                ->andReturn(['client_secret' => 'seti_2_secret', 'setup_intent_id' => 'seti_2']);
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->call('remove')
            ->assertSet('editing', true)
            ->assertSet('clientSecret', 'seti_2_secret')
            ->assertDispatched('toast-show');
    }

    public function test_a_broken_gateway_shows_the_notice_instead_of_the_element(): void
    {
        $user = User::factory()->create();

        $this->mockService(function (MockInterface $service): void {
            $service->shouldReceive('createSetupIntent')->andThrow(new \RuntimeException('stripe down'));
        });

        Livewire::actingAs($user)
            ->test(ProfilePayment::class)
            ->assertSet('paymentUnavailable', true)
            ->assertSet('clientSecret', null)
            ->assertSee(__('profile.payment_unavailable'));
    }

    private function userWithCard(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'stripe_customer_id' => 'cus_existing',
            'stripe_payment_method_id' => 'pm_saved',
            'card_brand' => 'visa',
            'card_last4' => '4242',
            'card_exp_month' => 12,
            'card_exp_year' => 2030,
            'card_holder' => 'Matteo Rossi',
        ])->save();

        return $user;
    }

    private function mockService(callable $expectations): void
    {
        $this->mock(SavedPaymentMethodService::class, $expectations);
    }
}
