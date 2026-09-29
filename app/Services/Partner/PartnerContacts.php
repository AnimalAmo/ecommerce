<?php

namespace App\Services\Partner;

use App\Models\Partner\PartnerProfile;
use App\Support\Phone;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * Che cosa una scheda può dire del suo partner. Serve due riquadri: la card
 * "Contatti" che sostituisce il box prenotazione quando lui non prende ordini
 * online (richiesta della cliente, 29/09/2026: «l'utente deve poter
 * visualizzare la scheda completa della struttura, con tutte le informazioni e
 * i contatti disponibili, senza dover procedere con il pagamento»), e le
 * «Informazioni utili» sotto il box prenotazione di chi incassa online.
 *
 * I recapiti sono voci NUOVE del profilo (risposta della cliente, 26/09/2026,
 * punto 6), mai quelli di registrazione o fatturazione: `users.phone` e
 * `users.email` sono credenziali di accesso, `partner_profiles.address` è la
 * sede legale, un dato fiscale. L'indirizzo che si pubblica è `public_address`,
 * che il partner sceglie di compilare. Stanno sul partner e non sulla
 * struttura (decisione di Matteo, 27/09/2026).
 *
 * Le regole di visibilità stanno SOLO qui, le viste mostrano quello che
 * ricevono:
 *  - senza consenso (PartnerProfile::publishesContacts()) i recapiti restano
 *    salvati ma non escono, né l'indirizzo né i link;
 *  - telefono, WhatsApp, email e sito permettono di scavalcare la piattaforma:
 *    escono solo per chi NON incassa online. Chi incassa su AnimalAmo si
 *    prenota dalla scheda, e lì quei link non si mostrano;
 *  - indirizzo pubblico e orari escono in entrambe le modalità. Gli orari non
 *    dipendono dal consenso: il partner li compila sapendo che compaiono sulle
 *    schede, lo dice l'hint del campo.
 *
 * Un'eccezione sta nelle viste: sulle attività e sugli eventi gratuiti
 * («Partecipa») la scheda non mostra la card contatti nemmeno per chi incassa
 * in struttura, e il riquadro «Informazioni utili» che c'è al suo posto
 * scarta i link che riceve (vedi partner-public-info.blade.php).
 */
class PartnerContacts
{
    public function __construct(private readonly PartnerPaymentModeService $modes) {}

    /**
     * Contatti del titolare della scheda, o null se non sappiamo dire nulla di utile.
     *
     * @return array{business_name: ?string, address: ?string, opening_hours: ?string, links: list<array{type: string, label: string, href: string}>, booking_url: ?string}|null
     */
    public function forPurchasable(?Model $purchasable): ?array
    {
        $owner = $purchasable?->getAttribute('user_id');

        return $this->forOwner($owner === null ? null : (int) $owner);
    }

    /**
     * Gli stessi contatti a partire dal solo id del titolare: il checkout sa chi
     * è il venditore del carrello (CartItemData::partnerUserId) ma non ha in
     * mano il purchasable, e caricarlo solo per rileggerne lo `user_id` sarebbe
     * una query per niente.
     *
     * Il profilo arriva da PartnerPaymentModeService, registrato `scoped`: la
     * scheda lo ha già letto per decidere la modalità, quindi qui non costa una
     * query in più.
     *
     * @return array{business_name: ?string, address: ?string, opening_hours: ?string, links: list<array{type: string, label: string, href: string}>, booking_url: ?string}|null
     */
    public function forOwner(?int $userId): ?array
    {
        $profile = $this->modes->profileFor($userId);

        if ($profile === null) {
            return null;
        }

        $consent = $profile->publishesContacts();
        $offline = ! $profile->requiresOnlinePayment();

        $contacts = [
            'business_name' => self::clean($profile->business_name),
            'address' => $consent ? self::clean($profile->public_address) : null,
            // Attributo tradotto con spatie: lingua corrente, poi l'italiano
            // (Translatable::fallback in AppServiceProvider). Senza traduzione
            // legge '' e non null, e clean() lo porta a null.
            'opening_hours' => self::clean($profile->opening_hours),
            'links' => $consent && $offline ? self::links($profile) : [],
            // Il link «dove pagare o prenotare»: a chi incassa online non serve,
            // si prenota dalla scheda. SafeUrl: il link finisce in un href, e
            // l'escape di Blade non ferma uno schema javascript: o data:.
            'booking_url' => $offline ? SafeUrl::http($profile->payment_url) : null,
        ];

        return array_filter($contacts) === [] ? null : $contacts;
    }

    /**
     * Il riquadro «Informazioni utili» ha qualcosa da mostrare? Una regola sola
     * per il partial e per le schede che decidono se aprirgli la colonna.
     * `$withHours` = false sulle attività, dove gli orari stanno già
     * nell'elenco informazioni della scheda.
     */
    public static function hasPublicInfo(?array $contacts, bool $withHours = true): bool
    {
        return filled($contacts['address'] ?? null)
            || ($withHours && filled($contacts['opening_hours'] ?? null));
    }

    /**
     * Link pronti per un href, nell'ordine in cui la card li mostra. I valori
     * li normalizza il form del profilo; qui si ripulisce comunque, come fa
     * SafeUrl, per le scritture che saltano il form (seeder, tinker, admin).
     * Una voce vuota o inservibile non produce un link.
     *
     * @return list<array{type: 'phone'|'whatsapp'|'email'|'website', label: string, href: string}>
     */
    private static function links(PartnerProfile $profile): array
    {
        $links = [];

        // Phone::toE164 anche qui: un numero scritto senza prefisso fuori dal
        // form («333 1234567» da un seeder) darebbe un wa.me senza paese.
        $phone = Phone::toE164(self::clean($profile->public_phone));
        $dial = preg_replace('/[^\d+]/', '', (string) $phone);

        if ($phone !== null && preg_match('/\d/', $dial)) {
            $links[] = ['type' => 'phone', 'label' => Phone::format($phone), 'href' => 'tel:'.$dial];
        }

        // wa.me vuole il numero internazionale in sole cifre, senza «+».
        $whatsapp = Phone::toE164(self::clean($profile->public_whatsapp));
        $digits = preg_replace('/\D/', '', (string) $whatsapp);

        if ($whatsapp !== null && $digits !== '') {
            $links[] = ['type' => 'whatsapp', 'label' => Phone::format($whatsapp), 'href' => 'https://wa.me/'.$digits];
        }

        $email = self::clean($profile->public_email);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            // FILTER_VALIDATE_EMAIL ammette ? = & % prima della chiocciola: con
            // la sola concatenazione «a?bcc=x@y.it» diventerebbe un Bcc nel
            // client di posta di chi scrive (RFC 6068). La parte locale va
            // codificata; il dominio, validato, non ha quei caratteri.
            $at = strrpos($email, '@');
            $href = 'mailto:'.rawurlencode(substr($email, 0, $at)).substr($email, $at);
            $links[] = ['type' => 'email', 'label' => $email, 'href' => $href];
        }

        $website = SafeUrl::http($profile->public_website);

        if ($website !== null) {
            // Sulla card si legge «www.esempio.it», non l'URL intero.
            $label = preg_replace('#^https?://#i', '', rtrim($website, '/'));
            $links[] = ['type' => 'website', 'label' => $label !== '' ? $label : $website, 'href' => $website];
        }

        return $links;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
