<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lo store database cancella una riga scaduta solo quando qualcuno rilegge
 * quella chiave: le chiavi usa e getta (anti-replay dei webhook Mailgun,
 * anelli della catena newsletter) resterebbero nella tabella per sempre.
 */
class PruneExpiredCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_rows_of_the_database_cache_go_and_live_ones_stay(): void
    {
        config(['cache.default' => 'database']);
        Cache::add('mailgun-webhook-token:vecchio', true, 600);
        Cache::add('newsletter:chain:abc:1', true, now()->addDay());

        $this->travel(2)->hours();
        Cache::add('mailgun-webhook-token:nuovo', true, 600);
        Cache::forever('impostazione', 'resta');

        $this->artisan('animalamo:prune-expired-cache')->assertSuccessful();

        $keys = DB::table('cache')->pluck('key');
        $this->assertCount(3, $keys);
        $this->assertFalse($keys->contains(fn (string $key): bool => str_ends_with($key, 'mailgun-webhook-token:vecchio')));
        $this->assertTrue($keys->contains(fn (string $key): bool => str_ends_with($key, 'newsletter:chain:abc:1')));
        $this->assertTrue($keys->contains(fn (string $key): bool => str_ends_with($key, 'mailgun-webhook-token:nuovo')));
        $this->assertSame('resta', Cache::get('impostazione'));
    }

    public function test_many_expired_rows_all_go(): void
    {
        config(['cache.default' => 'database']);
        $expired = now()->subMinute()->getTimestamp();

        foreach (array_chunk(range(1, 2500), 500) as $chunk) {
            DB::table('cache')->insert(array_map(fn (int $i): array => [
                'key' => "laravel-cache-mailgun-webhook-token:{$i}",
                'value' => serialize(true),
                'expiration' => $expired,
            ], $chunk));
        }

        $this->artisan('animalamo:prune-expired-cache')->assertSuccessful();

        $this->assertSame(0, DB::table('cache')->count());
    }

    /** Con un altro store (array nei test, redis un domani) la tabella non è sua. */
    public function test_with_another_cache_store_the_table_is_left_alone(): void
    {
        DB::table('cache')->insert([
            'key' => 'laravel-cache-riga-scaduta',
            'value' => serialize(true),
            'expiration' => now()->subDay()->getTimestamp(),
        ]);

        $this->artisan('animalamo:prune-expired-cache')->assertSuccessful();

        $this->assertSame(1, DB::table('cache')->count());
    }

    public function test_it_runs_every_day(): void
    {
        // Carica routes/console.php, dove stanno gli orari.
        $this->app->make(Kernel::class)->bootstrap();

        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'animalamo:prune-expired-cache'));

        $this->assertNotNull($event, 'Il comando non è schedulato.');
        $this->assertMatchesRegularExpression('/^\d+ \d+ \* \* \*$/', $event->expression);
    }
}
