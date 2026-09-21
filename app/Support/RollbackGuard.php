<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Freno per le down() che eliminano tabelle: se dentro c'è qualcosa, il
 * rollback si ferma invece di buttarlo. Svuotarle resta possibile, ma a mano
 * e dopo un backup: dev'essere una decisione, non l'effetto collaterale di
 * un `migrate:rollback --force` lanciato per rimediare a un deploy storto.
 *
 * Il rollback procede una migration alla volta e cancella ognuna dal
 * registro appena la sua down() finisce: fermarsi qui lascia il database
 * coerente, e un nuovo `migrate` riapplica solo quelle già tolte.
 */
final class RollbackGuard
{
    /** @throws RuntimeException se almeno una delle tabelle ha righe */
    public static function refuseToDropRows(string ...$tables): void
    {
        $filled = collect($tables)
            ->filter(fn (string $table): bool => Schema::hasTable($table))
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->filter();

        if ($filled->isEmpty()) {
            return;
        }

        // Solo italiano, come il resto del pannello: con APP_LOCALE=en il
        // messaggio uscirebbe come chiave grezza.
        throw new RuntimeException(__('admin.rollback_refused', [
            'tables' => $filled->map(fn (int $rows, string $table): string => "{$table} ({$rows})")->implode(', '),
        ], 'it'));
    }
}
