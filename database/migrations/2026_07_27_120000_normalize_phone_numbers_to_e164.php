<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Porta in E.164 i numeri già salvati nei tre formati che convivevano prima
 * di <x-phone-input> ("3331234567", "340 5738920", "+39 333 1234567").
 * Le righe non parsabili restano invariate: meglio un dato sporco che perso.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['users', 'orders', 'partner_applications'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::table($table)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $normalized = Phone::toE164($row->phone);

                        if ($normalized !== $row->phone) {
                            DB::table($table)->where('id', $row->id)->update(['phone' => $normalized]);
                        }
                    }
                });
        }
    }

    /**
     * Irreversibile per scelta: il formato originale non è ricostruibile e
     * quello E.164 resta comunque un numero valido.
     */
    public function down(): void {}
};
