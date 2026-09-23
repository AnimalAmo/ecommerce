<?php

namespace App\Support;

/**
 * Filtro per i link del partner stampati in un href (checkout, mail, pagine
 * ordine, schede). L'escape di Blade non ferma uno schema javascript: o data:,
 * quindi passa solo ciò che il browser apre come pagina web.
 *
 * La validazione di PartnerPaymentModeService::set() resta il primo argine:
 * questo copre le copie salvate sugli ordini e le scritture che saltano il
 * service (seeder, tinker, pannello admin futuro).
 */
final class SafeUrl
{
    /** L'URL ripulito se lo schema è http o https, altrimenti null (un href relativo "//host" compreso). */
    public static function http(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($scheme)) {
            return null;
        }

        return in_array(strtolower($scheme), ['http', 'https'], true) ? $url : null;
    }
}
