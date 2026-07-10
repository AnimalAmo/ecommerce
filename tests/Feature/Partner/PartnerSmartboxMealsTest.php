<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxMeals;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxMealsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_meal_options(): void
    {
        $this->get(route('partner.smartbox.meals'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_meals.heading'))
            ->assertSee(__('partner.smartbox_meals.step'))
            ->assertSee(__('partner.smartbox_meals.meal_breakfast'))
            ->assertSee(__('partner.smartbox_meals.meal_dinner'));
    }

    public function test_times_and_dietary_are_hidden_until_a_meal_is_offered(): void
    {
        Livewire::test(SmartboxMeals::class)
            ->assertDontSee(__('partner.smartbox_meals.dietary_heading'))
            ->set('meals', ['colazione'])
            ->assertSee(__('partner.smartbox_meals.times_heading'))
            ->assertSee(__('partner.smartbox_meals.dietary_heading'));
    }

    public function test_selecting_nessuno_clears_the_meals(): void
    {
        Livewire::test(SmartboxMeals::class)
            ->set('meals', ['colazione', 'cena'])
            ->set('meals', ['colazione', 'cena', 'nessuno'])
            ->assertSet('meals', ['nessuno']);
    }

    public function test_selecting_a_meal_clears_nessuno(): void
    {
        Livewire::test(SmartboxMeals::class)
            ->set('meals', ['nessuno'])
            ->set('meals', ['nessuno', 'pranzo'])
            ->assertSet('meals', ['pranzo']);
    }

    public function test_next_saves_meals_times_and_dietary(): void
    {
        Livewire::test(SmartboxMeals::class)
            ->set('meals', ['colazione'])
            ->set('mealTimes.colazione.from', '08:00')
            ->set('mealTimes.colazione.to', '10:00')
            ->set('dietary', ['vegano', 'senza_glutine'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.offers'));

        $draft = StructureDraft::first();
        $this->assertSame(['colazione'], $draft->meals);
        $this->assertSame(['vegano', 'senza_glutine'], $draft->dietary_restrictions);
        $this->assertSame('08:00', $draft->meal_times['colazione']['from']);
        $this->assertSame(6, $draft->current_step);
    }

    public function test_it_rehydrates_the_saved_food_choices(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 6,
            'meals' => ['cena'],
            'dietary_restrictions' => ['diabetico'],
            'meal_times' => ['cena' => ['from' => '19:00', 'to' => '21:00']],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxMeals::class)
            ->assertSet('meals', ['cena'])
            ->assertSet('dietary', ['diabetico']);
    }
}
