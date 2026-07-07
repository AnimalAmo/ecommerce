<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ruoli base della piattaforma (spec AnimalAmo: B2C client / partner B2B / superadmin).
 * In migration e non solo nel RoleSeeder: la registrazione assegna 'client' e deve
 * funzionare anche su un database solo migrato (in produzione il DatabaseSeeder
 * non è utilizzabile: crea gli utenti demo con password nota).
 */
return new class extends Migration
{
    private const ROLES = ['client', 'partner', 'superadmin'];

    public function up(): void
    {
        $now = now();

        foreach (self::ROLES as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', self::ROLES)->where('guard_name', 'web')->delete();
    }
};
