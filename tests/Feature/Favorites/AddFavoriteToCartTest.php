<?php

namespace Tests\Feature\Favorites;

use App\Enums\ProductType;
use App\Livewire\Commerce\Favorites;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\FavoriteService;
use Database\Seeders\DatabaseSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddFavoriteToCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_toggle_cart_adds_a_structure_hotel_with_the_detail_default_options(): void
    {
        $user = User::factory()->create();
        $hotel = Structure::where('type', ProductType::Structure)->orderBy('id')->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'structure', 'favoritable_id' => $hotel->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertOk()
            ->assertSet('inCart', [$favorite->id])
            ->assertDispatched('cart-updated');

        $items = app(CartManager::class)->items();
        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertSame('structure', $item->type);
        $this->assertSame($hotel->id, $item->purchasableId);
        $this->assertFalse($item->isGift);

        $today = new DateTimeImmutable('today');
        $this->assertSame($today->modify('+7 days')->format('Y-m-d'), $item->options['check_in']);
        $this->assertSame($today->modify('+12 days')->format('Y-m-d'), $item->options['check_out']);
        $this->assertSame(2, $item->options['guests']['adulti']);
        $this->assertSame(0, $item->options['guests']['ragazzi']);
        $this->assertSame(0, $item->options['guests']['bambini']);
        $this->assertSame(['cane' => 1], $item->options['animals']);
    }

    public function test_toggle_cart_adds_a_service_structure_with_day_and_time_slot(): void
    {
        $user = User::factory()->create();
        // Servizio "Centro di addestramento" (position 5): senza chiusure demo,
        // così il giorno di default (oggi+7) è aperto — la position 4 "Dog sitting"
        // ha invece una chiusura seedata proprio a oggi+7.
        $service = Structure::where('type', ProductType::Service)->where('position', 5)->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'structure', 'favoritable_id' => $service->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertDispatched('cart-updated');

        $item = app(CartManager::class)->items()->firstOrFail();
        $this->assertSame('structure', $item->type);
        $this->assertSame($service->id, $item->purchasableId);

        $today = new DateTimeImmutable('today');
        $this->assertSame($today->modify('+7 days')->format('Y-m-d'), $item->options['day']);
        $this->assertSame('10:00', $item->options['time_from']);
        $this->assertSame('16:00', $item->options['time_to']);
        $this->assertSame(['cane' => 1], $item->options['animals']);
        $this->assertArrayNotHasKey('guests', $item->options);
    }

    public function test_toggle_cart_adds_an_event_with_one_participant(): void
    {
        $user = User::factory()->create();
        $event = Event::where('type', ProductType::Event)
            ->where('is_free', false)
            ->whereNotNull('price_cents')
            ->orderBy('id')
            ->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertDispatched('cart-updated');

        $item = app(CartManager::class)->items()->firstOrFail();
        $this->assertSame('event', $item->type);
        $this->assertSame($event->id, $item->purchasableId);
        $this->assertFalse($item->isGift);
        $this->assertSame(['participants' => 1], $item->options);
    }

    public function test_toggle_cart_adds_an_activity_event_with_guests_and_animals(): void
    {
        $user = User::factory()->create();
        $activity = Event::where('type', ProductType::Activity)
            ->where('is_free', false)
            ->whereNotNull('price_cents')
            ->orderBy('id')
            ->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $activity->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertDispatched('cart-updated');

        $item = app(CartManager::class)->items()->firstOrFail();
        $this->assertSame('event', $item->type);
        $this->assertSame($activity->id, $item->purchasableId);
        $this->assertSame(2, $item->options['guests']['adulti']);
        $this->assertSame(['cane' => 1], $item->options['animals']);
        $this->assertArrayNotHasKey('participants', $item->options);
    }

    public function test_toggle_cart_adds_a_smartbox_without_gift(): void
    {
        $user = User::factory()->create();
        $box = SmartboxPackage::orderBy('id')->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'smartbox_package', 'favoritable_id' => $box->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertDispatched('cart-updated');

        $item = app(CartManager::class)->items()->firstOrFail();
        $this->assertSame('smartbox_package', $item->type);
        $this->assertSame($box->id, $item->purchasableId);
        $this->assertFalse($item->isGift);
        $this->assertSame(['animals' => ['cane' => 1]], $item->options);
    }

    public function test_guest_toggle_cart_opens_the_login_modal_and_adds_nothing(): void
    {
        Livewire::test(Favorites::class)
            ->call('toggleCart', 1)
            ->assertOk()
            ->assertDispatched('modal-show', name: 'login')
            ->assertNotDispatched('cart-updated')
            ->assertSet('inCart', []);
    }

    public function test_second_toggle_cart_click_is_a_no_op(): void
    {
        $user = User::factory()->create();
        $event = Event::where('type', ProductType::Event)
            ->where('is_free', false)
            ->whereNotNull('price_cents')
            ->orderBy('id')
            ->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->call('toggleCart', $favorite->id)
            ->assertSet('inCart', [$favorite->id]);

        $this->assertCount(1, app(CartManager::class)->items());
    }

    public function test_free_event_favorite_is_not_purchasable_and_hides_the_bag(): void
    {
        $user = User::factory()->create();
        $freeEvent = Event::where('is_free', true)->orderBy('id')->firstOrFail();
        $favorite = $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $freeEvent->id]);

        // Il bottone borsa non deve nemmeno comparire per un evento gratuito ("Partecipa").
        $cards = app(FavoriteService::class)->cards($user);
        $this->assertFalse($cards[0]['can_add_to_cart']);

        // E se invocato comunque (payload forgiato), è un no-op silenzioso: niente carrello, niente errore.
        Livewire::actingAs($user)->test(Favorites::class)
            ->call('toggleCart', $favorite->id)
            ->assertOk()
            ->assertNotDispatched('cart-updated');

        $this->assertCount(0, app(CartManager::class)->items());
    }

    public function test_cards_expose_the_morph_alias_and_product_key_for_cart_adds(): void
    {
        $user = User::factory()->create();
        $box = SmartboxPackage::orderBy('id')->firstOrFail();
        $user->favorites()->create(['favoritable_type' => 'smartbox_package', 'favoritable_id' => $box->id]);

        $cards = app(FavoriteService::class)->cards($user);

        $this->assertSame('smartbox_package', $cards[0]['favoritable_type']);
        $this->assertSame($box->id, $cards[0]['favoritable_id']);
    }
}
