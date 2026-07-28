<?php

namespace App\Services\Partner;

use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Chiusura dell'iscrizione B2B (step 2): dai dati dello step 1 nasce l'account
 * partner. Due strade, stessa transazione:
 *
 *  - visitatore → si crea un utente nuovo, attivo, con ruolo partner;
 *  - utente ecommerce già registrato → si PROMUOVE il suo account, che tiene
 *    il ruolo `client` (carrello, ordini, preferiti restano suoi) e guadagna
 *    `partner`. Senza questo ramo l'email risulterebbe già presa e la
 *    richiesta "diventa partner" finirebbe in un vicolo cieco.
 *
 * In entrambi i casi nasce/si aggiorna il profilo fiscale B2B e la
 * candidatura collegata passa a `registered`.
 */
class RegisterPartnerAccount
{
    /**
     * @param  array<string, string>  $step1  dati validati dello step 1 (camelCase)
     * @param  User|null  $account  account B2C da promuovere; null = nuovo utente
     */
    public function register(array $step1, ?User $account = null, ?int $applicationId = null): User
    {
        return DB::transaction(function () use ($step1, $account, $applicationId): User {
            $user = $account === null
                ? $this->createAccount($step1)
                : $this->promote($account, $step1);

            // updateOrCreate: un utente promosso potrebbe già avere il profilo
            // (seconda attività, oppure ritorno sullo step 2).
            $user->partnerProfile()->updateOrCreate([], [
                'business_name' => $step1['businessName'],
                'vat' => $step1['vat'],
                'tax_code' => $step1['taxCode'],
                'pec' => $step1['pec'],
                'sdi' => $step1['sdi'],
                'address' => $step1['address'],
                'province' => $step1['province'],
                'zip' => $step1['zip'],
            ]);

            if ($applicationId !== null) {
                PartnerApplication::whereKey($applicationId)->update([
                    'user_id' => $user->id,
                    'status' => PartnerApplication::STATUS_REGISTERED,
                    'registered_at' => now(),
                ]);
            }

            return $user;
        });
    }

    /**
     * Password casuale: il mockup non prevede il campo — l'accesso post-logout
     * arriverà col flusso "imposta password" (TODO, non ancora disegnato).
     *
     * @param  array<string, string>  $step1
     */
    private function createAccount(array $step1): User
    {
        $user = User::create([
            'first_name' => $step1['firstName'],
            'last_name' => $step1['lastName'],
            'email' => $step1['email'],
            'phone' => $step1['phone'],
            'password' => Str::password(32),
            'is_active' => true,
        ]);

        $user->syncRoles(['partner']);

        return $user;
    }

    /**
     * `assignRole` e non `syncRoles`: l'utente promosso resta anche cliente.
     * Nome e cellulare seguono quanto riscritto nello step 1 (l'email no: è
     * bloccata sull'account, decide quale utente promuovere). `is_active` non
     * si tocca: un account disattivato non torna attivo da qui.
     *
     * @param  array<string, string>  $step1
     */
    private function promote(User $user, array $step1): User
    {
        $user->update([
            'first_name' => $step1['firstName'],
            'last_name' => $step1['lastName'],
            'phone' => $step1['phone'],
        ]);

        $user->assignRole('partner');

        return $user;
    }
}
