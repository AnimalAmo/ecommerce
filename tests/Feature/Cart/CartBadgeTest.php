<?php

namespace Tests\Feature\Cart;

use App\Livewire\Commerce\CartBadge;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_is_hidden_when_cart_is_empty(): void
    {
        Livewire::test(CartBadge::class)
            ->assertDontSeeHtml('bg-brand-yellow');
    }

    public function test_badge_shows_cart_items_count(): void
    {
        $user = User::factory()->create();
        $box = SmartboxPackage::factory()->create(['price_cents' => 21500]);

        $this->actingAs($user);
        app(CartManager::class)->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);

        Livewire::test(CartBadge::class)
            ->assertSeeHtml('bg-brand-yellow')
            ->assertSee('1');
    }

    public function test_badge_refreshes_on_cart_updated_event(): void
    {
        $user = User::factory()->create();
        $box = SmartboxPackage::factory()->create(['price_cents' => 21500]);
        $this->actingAs($user);

        $component = Livewire::test(CartBadge::class)
            ->assertDontSeeHtml('bg-brand-yellow');

        app(CartManager::class)->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);

        $component->dispatch('cart-updated')
            ->assertSeeHtml('bg-brand-yellow')
            ->assertSee('1');
    }

    public function test_header_renders_cart_badge_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeLivewire(CartBadge::class);
    }
}
