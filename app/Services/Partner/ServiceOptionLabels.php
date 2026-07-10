<?php

namespace App\Services\Partner;

/**
 * Cataloga le opzioni-chiave che il wizard partner salva su StructureDraft
 * (checkbox/radio: servizi, regole, servizi animali, tipologie...) e le
 * risolve nella label localizzata. Le chiavi su DB sono slug stabili; la
 * lingua la decide il lang file — così il dettaglio servizio è multilingua.
 */
class ServiceOptionLabels
{
    /** Mappa chiave draft => chiave lang, per gruppo. */
    private const MAPS = [
        'services' => [
            'aria_condizionata' => 'partner.hotel_services.svc_ac',
            'riscaldamento' => 'partner.hotel_services.svc_heating',
            'wifi' => 'partner.hotel_services.svc_wifi',
            'ricarica_elettrica' => 'partner.hotel_services.svc_ev',
            'tv' => 'partner.hotel_services.svc_tv',
            'piscina' => 'partner.hotel_services.svc_pool',
            'sauna' => 'partner.hotel_services.svc_sauna',
        ],
        'additional' => [
            'nessuno' => 'partner.hotel_services.add_none',
            'colazione' => 'partner.hotel_services.add_breakfast',
            'pranzo' => 'partner.hotel_services.add_lunch',
            'cena' => 'partner.hotel_services.add_dinner',
            'altro' => 'partner.hotel_services.add_other',
        ],
        'rules' => [
            'vietato_fumare' => 'partner.hotel_services.rule_no_smoking',
            'vietato_feste' => 'partner.hotel_services.rule_no_parties',
        ],
        'animal_services' => [
            'nessuno' => 'partner.hotel_animal_services.opt_none',
            'omaggio' => 'partner.hotel_animal_services.opt_welcome',
            'pet_sitting' => 'partner.hotel_animal_services.opt_petsitting',
            'veterinario' => 'partner.hotel_animal_services.opt_vet',
            'area_animali' => 'partner.hotel_animal_services.opt_area',
            'altro' => 'partner.hotel_animal_services.opt_other',
        ],
        'type' => [
            'hotel' => 'partner.structure_type.hotel',
            'bb' => 'partner.structure_type.bb',
            'agriturismo' => 'partner.structure_type.agriturismo',
            'attivita' => 'partner.activity_type.attivita',
            'eventi' => 'partner.activity_type.eventi',
            'soggiorno' => 'partner.smartbox_type.soggiorno',
            'benessere' => 'partner.smartbox_type.benessere',
            'avventura' => 'partner.smartbox_type.avventura',
        ],
    ];

    /** Label localizzata di una singola chiave (fallback: la chiave stessa). */
    public static function label(string $group, ?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $langKey = self::MAPS[$group][$key] ?? null;

        return $langKey ? __($langKey) : $key;
    }

    /** Label localizzate di una lista di chiavi, nell'ordine dato. */
    public static function labels(string $group, ?array $keys): array
    {
        return array_map(fn ($key) => self::label($group, $key), $keys ?? []);
    }
}
