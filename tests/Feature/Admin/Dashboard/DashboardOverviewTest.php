<?php

namespace Tests\Feature\Admin\Dashboard;

use App\Models\Community\CommunityPost;
use App\Models\ContactMessage\ContactMessage;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\Partner\PartnerApplication;
use App\Models\Review\Review;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Dashboard\DashboardOverview;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** I numeri della home, costruiti a mano. */
class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        // Lunedì 21 settembre 2026, le 10 in Italia.
        $this->travelTo(CarbonImmutable::parse('2026-09-21 08:00:00', 'UTC'));
    }

    public function test_todo_counts_what_waits_and_how_old_the_oldest_approval_is(): void
    {
        config(['admin.moderation' => true]);
        Structure::factory()->create(['approval_status' => 'pending', 'approval_requested_at' => now()->subDays(3)]);
        Event::factory()->create(['approval_status' => 'pending', 'approval_requested_at' => now()->subDay()]);
        SmartboxPackage::factory()->create();
        Review::factory()->create(['status' => 'pending']);
        Review::factory()->create(['status' => 'published']);
        CommunityPost::factory()->create(['reports_count' => 2]);
        CommunityPost::factory()->create(['reports_count' => 1, 'hidden_at' => now()]);
        $this->message('Sara', now()->subHour());
        $this->message('Luca', now()->subHours(2), ['handled_at' => now()]);
        $this->application('Marta', now()->subHour());
        $this->application('Giulio', now()->subDays(2));
        $this->application('Paolo', now()->subDays(3), ['archived_at' => now()]);

        $this->assertSame([
            'approvals' => 2,
            'oldestApprovalDays' => 3,
            'showApprovals' => true,
            'applications' => 2,
            'messages' => 1,
            'reviews' => 1,
            'flaggedPosts' => 1,
        ], $this->overview()->todo());
    }

    public function test_the_approvals_tile_stays_hidden_while_moderation_is_off_and_nothing_waits(): void
    {
        config(['admin.moderation' => false]);

        $this->assertFalse($this->overview()->todo()['showApprovals']);
        $this->assertNull($this->overview()->todo()['oldestApprovalDays']);

        // Una scheda in attesa si mostra comunque: è rimasta indietro da quando la moderazione era accesa.
        Structure::factory()->create(['approval_status' => 'pending']);
        // AdminCounters memorizza per richiesta: qui la "richiesta" successiva.
        $this->app->forgetScopedInstances();

        $this->assertTrue($this->overview()->todo()['showApprovals']);
    }

    public function test_catalog_counts_published_and_suspended_across_the_three_families(): void
    {
        Structure::factory()->create();
        Structure::factory()->create(['suspended_at' => now()]);
        Event::factory()->create();
        Event::factory()->create(['approval_status' => 'pending']);
        SmartboxPackage::factory()->create();
        SmartboxPackage::factory()->create(['approval_status' => 'changes_requested', 'suspended_at' => now()]);

        $this->assertSame(['published' => 3, 'suspended' => 2], $this->overview()->catalogCounts());
    }

    public function test_subscribers_leave_out_superadmins_and_count_the_last_thirty_days(): void
    {
        Role::findOrCreate('superadmin', 'web');
        User::factory()->create(['created_at' => now()->subDays(90)]);
        User::factory()->create(['created_at' => now()->subDays(30)->subSecond()]);
        User::factory()->create(['created_at' => now()->subDays(30)]);
        User::factory()->create(['created_at' => now()->subDay()]);
        User::factory()->create(['created_at' => now()->subDay()])->assignRole('superadmin');

        $this->assertSame(['total' => 4, 'recent' => 2], $this->overview()->subscribers());
    }

    public function test_partners_count_the_active_ones_and_those_without_listings(): void
    {
        Role::findOrCreate('partner', 'web');
        Role::findOrCreate('client', 'web');

        $withStructure = $this->partnerUser();
        Structure::factory()->for($withStructure)->create();
        // Una scheda sospesa è pur sempre una scheda.
        $withSuspendedEvent = $this->partnerUser();
        Event::factory()->for($withSuspendedEvent)->create(['suspended_at' => now()]);
        $this->partnerUser();
        $this->partnerUser(['is_active' => false]);
        $this->partnerUser(['anonymized_at' => now()]);
        User::factory()->create(['is_active' => true])->assignRole('client');

        $this->assertSame(['active' => 3, 'withoutListings' => 1], $this->overview()->partners());
    }

    public function test_month_sales_count_the_italian_month(): void
    {
        foreach (['2026-08-31 23:30' => 1000, '2026-09-01 00:30' => 2000, '2026-09-21 09:00' => 3000] as $time => $total) {
            Order::factory()->guest()->paid()->create([
                'total_cents' => $total,
                'created_at' => CarbonImmutable::parse($time, 'Europe/Rome')->utc(),
            ]);
        }

        $this->assertSame(['orders' => 2, 'gross' => 5000], $this->overview()->monthSales());
    }

    public function test_month_on_site_bookings_stay_out_of_month_sales(): void
    {
        Order::factory()->guest()->paid()->create([
            'total_cents' => 3000,
            'created_at' => CarbonImmutable::parse('2026-09-10 09:00', 'Europe/Rome')->utc(),
        ]);
        Order::factory()->guest()->onSite()->create([
            'total_cents' => 9000,
            'created_at' => CarbonImmutable::parse('2026-09-12 09:00', 'Europe/Rome')->utc(),
        ]);
        Order::factory()->guest()->onSite()->create([
            'total_cents' => 1000,
            'created_at' => CarbonImmutable::parse('2026-08-31 23:30', 'Europe/Rome')->utc(),
        ]);

        $this->assertSame(['orders' => 1, 'gross' => 3000], $this->overview()->monthSales());
        $this->assertSame(['count' => 1, 'value_cents' => 9000], $this->overview()->monthOnSiteBookings());
    }

    public function test_latest_listings_mix_the_families_newest_first_hidden_ones_included(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Hotel vecchio'], 'created_at' => now()->subDays(9)]);
        Structure::factory()->create(['name' => ['it' => 'Hotel sospeso'], 'created_at' => now()->subDays(2), 'suspended_at' => now()]);
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'created_at' => now()->subHours(2)]);
        Event::factory()->create(['title' => ['it' => 'Evento in attesa'], 'created_at' => now()->subDay(), 'approval_status' => 'pending']);
        SmartboxPackage::factory()->create(['title' => ['it' => 'Relax'], 'created_at' => now()->subDays(3)]);

        $names = $this->overview()->latestListings(4)
            ->map(fn ($item) => $item->getTranslation($item instanceof Structure ? 'name' : 'title', 'it'))
            ->all();

        $this->assertSame(['Puppy Yoga', 'Evento in attesa', 'Hotel sospeso', 'Relax'], $names);
    }

    public function test_the_inbox_merges_messages_and_applications_newest_first_without_archived_ones(): void
    {
        $this->message('Sara', now()->subMinutes(10));
        $this->message('Archiviato', now()->subMinutes(5), ['archived_at' => now()]);
        $this->application('Marta', now()->subHour(), ['business_name' => 'Dog Academy', 'offer_type' => 'Attività', 'city' => 'Brescia']);
        $this->message('Luca', now()->subDays(1), ['handled_at' => now()]);
        $this->application('Giulio', now()->subDays(2));
        $this->application('Paolo', now()->subDays(4));

        $inbox = $this->overview()->inbox(4);

        $this->assertSame(['Sara Rossi', 'Marta Rossi', 'Luca Rossi', 'Giulio Rossi'], $inbox->pluck('who')->all());
        $this->assertSame(['message', 'application', 'message', 'application'], $inbox->pluck('kind')->all());
        $this->assertSame('Dog Academy — Attività, Brescia', $inbox[1]['what']);
        $this->assertSame(
            route('admin.inbox', ['tab' => 'applications', 'id' => PartnerApplication::query()->where('first_name', 'Marta')->value('id')]),
            $inbox[1]['url'],
        );
    }

    public function test_the_greeting_follows_the_italian_clock(): void
    {
        $this->assertSame('morning', $this->overview()->greeting());

        $this->travelTo(CarbonImmutable::parse('2026-09-21 11:30:00', 'UTC'));
        $this->assertSame('afternoon', $this->overview()->greeting(), '13:30 in Italia');

        $this->travelTo(CarbonImmutable::parse('2026-09-21 17:00:00', 'UTC'));
        $this->assertSame('evening', $this->overview()->greeting(), '19:00 in Italia');
    }

    // --- dati costruiti a mano -------------------------------------------------

    private function overview(): DashboardOverview
    {
        return app(DashboardOverview::class);
    }

    private function message(string $firstName, CarbonInterface $at, array $extra = []): ContactMessage
    {
        return ContactMessage::query()->forceCreate(array_merge([
            'first_name' => $firstName,
            'last_name' => 'Rossi',
            'email' => mb_strtolower($firstName).'@example.com',
            'reason' => 'Informazioni generali',
            'message' => 'Il cane può stare in camera?',
            'created_at' => $at,
            'updated_at' => $at,
        ], $extra));
    }

    private function application(string $firstName, CarbonInterface $at, array $extra = []): PartnerApplication
    {
        return PartnerApplication::query()->forceCreate(array_merge([
            'first_name' => $firstName,
            'last_name' => 'Rossi',
            'email' => mb_strtolower($firstName).'@example.com',
            'phone' => '+393331234567',
            'city' => 'Mantova',
            'business_name' => 'Pet Service',
            'role' => 'Titolare',
            'offer_type' => 'Servizi per animali',
            'description' => 'Dog sitter.',
            'created_at' => $at,
            'updated_at' => $at,
        ], $extra));
    }

    private function partnerUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole('partner');

        return $user;
    }
}
