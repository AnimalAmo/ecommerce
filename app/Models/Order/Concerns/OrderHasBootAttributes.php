<?php

namespace App\Models\Order\Concerns;

use App\Models\Order\Order;
use App\Services\Shared\SequentialNumberGenerator;
use Illuminate\Support\Str;

trait OrderHasBootAttributes
{
    /**
     * Prefisso del placeholder pre-insert: tiene buono il NOT NULL + unique di
     * order_number fino al post-insert, quando arriva il numero definitivo.
     */
    private const PENDING_ORDER_NUMBER_PREFIX = 'PND-';

    /**
     * Niente lettura max(id) + lockForUpdate pre-insert (gap-lock deadlock fra
     * checkout concorrenti a tabella vuota/coda): il numero definitivo nasce
     * in created dall'id auto-increment REALE con un update mirato — resta
     * sequenziale per costruzione. SequentialNumberGenerator resta disponibile
     * per i futuri model numerati senza questo profilo di concorrenza.
     */
    public static function bootOrderHasBootAttributes(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = self::PENDING_ORDER_NUMBER_PREFIX.Str::ulid();
            }
        });

        static::created(function (Order $order): void {
            if (str_starts_with((string) $order->order_number, self::PENDING_ORDER_NUMBER_PREFIX)) {
                $order->updateQuietly(['order_number' => static::formatOrderNumber($order->id)]);
            }
        });
    }

    public static function formatOrderNumber(int $id): string
    {
        return sprintf('ORD-%s', str_pad((string) $id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Numero esplicito pre-insert per i chiamanti fuori dal path checkout
     * (seeder demo): fuori dalla concorrenza reale il max(id)+lock basta.
     */
    public static function generateOrderNumber(): string
    {
        return app(SequentialNumberGenerator::class)->next(Order::class, 'ORD', 6);
    }
}
