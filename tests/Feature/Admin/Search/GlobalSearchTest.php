<?php

namespace Tests\Feature\Admin\Search;

use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Search\GlobalSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private GlobalSearch $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = app(GlobalSearch::class);
    }

    public function test_a_term_needs_two_characters(): void
    {
        $this->assertFalse($this->search->isSearchable(''));
        $this->assertFalse($this->search->isSearchable(' a '));
        $this->assertTrue($this->search->isSearchable('ab'));
    }

    public function test_the_catalog_is_searched_with_suspended_and_pending_listings(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Hotel Brescia'], 'suspended_at' => now()]);
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'location' => 'Brescia, Italia', 'approval_status' => 'pending']);
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create(['business_name' => 'Brescia Pet srl']);
        SmartboxPackage::factory()->for($partner)->create(['title' => ['it' => 'Relax in Lombardia']]);
        Structure::factory()->create(['name' => ['it' => 'Villa Mantova'], 'location' => 'Mantova, Italia']);

        $result = $this->search->catalog('brescia');

        $this->assertSame(3, $result['total']);
        $this->assertEqualsCanonicalizing(
            ['Hotel Brescia', 'Puppy Yoga', 'Relax in Lombardia'],
            $result['items']->map(fn ($item) => $item->getTranslation($item instanceof Structure ? 'name' : 'title', 'it'))->all(),
        );
    }

    public function test_users_match_every_word_on_name_or_email_and_superadmins_stay_out(): void
    {
        Role::findOrCreate('superadmin', 'web');
        $mario = User::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi', 'email' => 'mario@example.com']);
        User::factory()->create(['first_name' => 'Maria', 'last_name' => 'Bianchi', 'email' => 'mbianchi@example.com']);
        User::factory()->create(['first_name' => 'Luca', 'last_name' => 'Rossi', 'email' => 'luca.rossi@example.com']);
        User::factory()->create(['first_name' => 'Mario', 'last_name' => 'Admin', 'email' => 'admin@example.com'])->assignRole('superadmin');

        $this->assertSame([$mario->id], $this->search->users('mario rossi')['items']->pluck('id')->all());
        $this->assertSame(2, $this->search->users('ROSSI')['total']);
        $this->assertSame(1, $this->search->users('mbianchi@')['total']);
        $this->assertSame(1, $this->search->users('mario')['total'], 'il superadmin non è un iscritto');
    }

    public function test_the_lists_are_capped_but_the_total_is_not(): void
    {
        User::factory()->count(GlobalSearch::LIMIT + 2)->create(['last_name' => 'Ferrari']);

        $result = $this->search->users('ferrari');

        $this->assertCount(GlobalSearch::LIMIT, $result['items']);
        $this->assertSame(GlobalSearch::LIMIT + 2, $result['total']);
    }

    public function test_orders_are_found_by_number_the_exact_one_first(): void
    {
        foreach (['ORD-000142', 'ORD-000042', 'ORD-000420', 'ORD-000007'] as $number) {
            Order::factory()->guest()->create(['order_number' => $number]);
        }

        $numbers = $this->search->orders('42')['items']->pluck('order_number');

        $this->assertSame('ORD-000042', $numbers->first(), 'il numero esatto per primo');
        $this->assertEqualsCanonicalizing(['ORD-000142', 'ORD-000420'], $numbers->slice(1)->values()->all());
        $this->assertSame('ORD-000042', $this->search->orders('ord-000042')['items']->first()->order_number);
        $this->assertSame('ORD-000007', $this->search->orders('ORD-7')['items']->first()->order_number);
        $this->assertSame(0, $this->search->orders('mario')['total'], 'senza cifre non è un numero d\'ordine');
    }
}
