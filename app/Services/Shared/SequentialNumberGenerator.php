<?php

namespace App\Services\Shared;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Numeri sequenziali leggibili (ORD-000042, …) basati sul prossimo id libero
 * del model. Port da matsuri-nerd (senza il ramo SoftDeletes: qui nessun model
 * numerato usa soft delete).
 *
 * La lettura max(id) è in transaction con lockForUpdate per ridurre le race
 * fra writer concorrenti (non una garanzia assoluta su MySQL REPEATABLE READ,
 * ma elimina la race del max+1 ingenuo nei booted hook).
 */
class SequentialNumberGenerator
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function next(string $modelClass, string $prefix, int $padLength = 6): string
    {
        $nextId = $this->db->transaction(function () use ($modelClass): int {
            return (int) ($modelClass::query()->lockForUpdate()->max('id') ?? 0) + 1;
        });

        return sprintf('%s-%s', $prefix, str_pad((string) $nextId, $padLength, '0', STR_PAD_LEFT));
    }
}
