<?php

namespace Tests\Unit;

use App\Models\Order\Order;
use App\Services\Shared\SequentialNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequentialNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private SequentialNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(SequentialNumberGenerator::class);
    }

    public function test_first_number_starts_from_one_with_default_padding(): void
    {
        $this->assertSame('ORD-000001', $this->generator->next(Order::class, 'ORD', 6));
    }

    public function test_number_follows_the_max_id(): void
    {
        Order::factory()->create();
        Order::factory()->create();

        $this->assertSame('ORD-000003', $this->generator->next(Order::class, 'ORD', 6));
    }

    public function test_custom_prefix_and_pad_length(): void
    {
        $this->assertSame('INV-001', $this->generator->next(Order::class, 'INV', 3));
    }

    public function test_padding_does_not_truncate_ids_longer_than_pad_length(): void
    {
        Order::factory()->create(['id' => 12345]);

        $this->assertSame('ORD-12346', $this->generator->next(Order::class, 'ORD', 3));
    }

    public function test_sequential_creates_produce_unique_sequential_numbers(): void
    {
        // Concorrenza basica: N create ravvicinate (stesso processo) non devono
        // mai riusare un numero — ogni creating hook vede il max(id) aggiornato.
        $numbers = Order::factory()
            ->count(5)
            ->create()
            ->pluck('order_number');

        $this->assertSame(
            ['ORD-000001', 'ORD-000002', 'ORD-000003', 'ORD-000004', 'ORD-000005'],
            $numbers->all(),
        );
        $this->assertSame(5, $numbers->unique()->count());
    }
}
