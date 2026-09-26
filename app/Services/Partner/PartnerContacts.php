<?php

namespace App\Services\Partner;

use App\Models\Partner\PartnerProfile;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * Che cosa sappiamo del partner di una scheda, per la card "Contatti" che
 * sostituisce il box prenotazione quando lui non prende ordini online
 * (richiesta della cliente, 29/09/2026: «l'utente deve poter visualizzare la
 * scheda completa della struttura, con tutte le informazioni e i contatti
 * disponibili, senza dover procedere con il pagamento»).
 *
 * ATTENZIONE, è il limite di questa versione: a database NON esistono un
 * telefono, un'email pubblica, un sito o degli orari del partner. `users.phone`
 * e `users.email` sono credenziali di accesso e dati di fatturazione, non
 * recapiti che si pubblicano d'ufficio. Qui si usa solo ciò che il partner ha
 * già dato sapendo che sarebbe stato pubblico: ragione sociale, indirizzo
 * dell'attività e il link «dove pagare o prenotare» del suo profilo.
 *
 * Aggiungere telefono, email e orari significa nuove colonne su
 * `partner_profiles`, i campi nel profilo partner e una spunta di consenso:
 * quando arriveranno, entrano da qui e le viste non cambiano.
 */
class PartnerContacts
{
    public function __construct(private readonly PartnerPaymentModeService $modes) {}

    /** Contatti del titolare della scheda, o null se non sappiamo dire nulla di utile. */
    public function forPurchasable(?Model $purchasable): ?array
    {
        $owner = $purchasable?->getAttribute('user_id');
        $profile = $this->modes->profileFor($owner === null ? null : (int) $owner);

        if ($profile === null) {
            return null;
        }

        $contacts = [
            'business_name' => self::clean($profile->business_name),
            'address' => self::address($profile),
            // SafeUrl: il link finisce in un href, e l'escape di Blade non
            // ferma uno schema javascript: o data:.
            'website' => SafeUrl::http($profile->payment_url),
        ];

        return array_filter($contacts) === [] ? null : $contacts;
    }

    /** Indirizzo su una riga: via, CAP città (PROV). Le parti mancanti spariscono. */
    private static function address(PartnerProfile $profile): ?string
    {
        $town = trim(implode(' ', array_filter([
            self::clean($profile->zip),
            self::clean($profile->city),
            filled($profile->province) ? '('.trim((string) $profile->province).')' : null,
        ])));

        $line = implode(', ', array_filter([self::clean($profile->address), $town !== '' ? $town : null]));

        return $line !== '' ? $line : null;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
