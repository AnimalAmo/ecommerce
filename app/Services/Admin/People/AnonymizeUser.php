<?php

namespace App\Services\Admin\People;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * "Cancella su richiesta" (diritto all'oblio): l'account non si elimina, si
 * anonimizza. Gli ordini restano intatti, con i loro snapshot dell'acquirente,
 * perché la conservazione dei documenti fiscali prevale sulla cancellazione;
 * tutto il resto che identifica la persona se ne va.
 *
 * Un utente anonimizzato non può più entrare (LoginForm e EnsureSuperadmin
 * guardano `anonymized_at`) e la sua email diventa un segnaposto `.invalid`,
 * quindi nemmeno il reset password lo raggiunge.
 */
class AnonymizeUser
{
    public const DISPLAY_NAME = 'Utente anonimizzato';

    /** Tabelle del catalogo in cui un partner ha schede proprie (colonna user_id). */
    private const CATALOG_TABLES = ['structures', 'events', 'smartbox_packages'];

    /**
     * Perché questo utente non si può anonimizzare, o null se si può.
     * Il testo va in modale così com'è.
     */
    public function blockReason(User $user): ?string
    {
        if ($user->anonymized_at !== null) {
            return 'I dati di questo utente sono già stati cancellati.';
        }

        if ($user->hasRole('superadmin')) {
            return 'Un amministratore del pannello non si può anonimizzare da qui.';
        }

        if ($user->is_active && $user->hasRole('partner') && $this->liveCatalogItems($user) > 0) {
            return 'È un partner attivo con schede a catalogo: sospendi prima le sue schede e disattiva l\'account, poi potrai cancellare i suoi dati.';
        }

        return null;
    }

    public function handle(User $user): void
    {
        if (($reason = $this->blockReason($user)) !== null) {
            throw new RuntimeException($reason);
        }

        DB::transaction(function () use ($user): void {
            $email = $user->email;

            $user->pets()->delete();
            $user->favorites()->delete();
            $carts = DB::table('carts')->where('user_id', $user->id)->pluck('id');
            DB::table('cart_items')->whereIn('cart_id', $carts)->delete();
            DB::table('carts')->whereIn('id', $carts)->delete();

            // La prova del consenso di una persona che chiede la cancellazione
            // non serve più: la riga se ne va, per user_id e per email.
            DB::table('newsletter_subscribers')
                ->where('user_id', $user->id)
                ->orWhere('email', mb_strtolower($email))
                ->delete();

            // I contenuti pubblici restano, senza il nome di chi li ha scritti.
            foreach (['community_posts', 'community_post_replies'] as $table) {
                DB::table($table)->where('user_id', $user->id)->update(['author_name' => self::DISPLAY_NAME]);
            }

            DB::table('reviews')->where('user_id', $user->id)->update([
                'author_name' => self::DISPLAY_NAME,
                'author_initials' => 'UA',
            ]);

            $user->forceFill([
                'first_name' => self::DISPLAY_NAME,
                'last_name' => '',
                'email' => "anonimo-{$user->id}@anonimizzato.invalid",
                'email_verified_at' => null,
                'phone' => null,
                'address' => null,
                'city' => null,
                'postal_code' => null,
                'birth_date' => null,
                'password' => Str::random(64),
                'remember_token' => Str::random(60),
                'newsletter' => false,
                'marketing_consent' => false,
                'is_active' => false,
                'stripe_customer_id' => null,
                'stripe_payment_method_id' => null,
                'card_brand' => null,
                'card_last4' => null,
                'card_exp_month' => null,
                'card_exp_year' => null,
                'card_holder' => null,
                'anonymized_at' => now(),
            ])->save();

            // Sessioni aperte chiuse subito, non alla prossima scadenza.
            if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });
    }

    private function liveCatalogItems(User $user): int
    {
        return collect(self::CATALOG_TABLES)->sum(fn (string $table): int => DB::table($table)
            ->where('user_id', $user->id)
            ->whereNull('suspended_at')
            ->count());
    }
}
