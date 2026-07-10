<?php

namespace Database\Seeders;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Utenti demo: Giulia Rossi (cliente del mock XD "Il mio account") e un partner
 * per il login B2B. Va eseguito DOPO i seeder catalogo (i preferiti puntano lì).
 *
 * Preferiti di Giulia — l'artboard XD "Preferiti – 2" cita prodotti fuori
 * catalogo mock (vedi commento su App\Livewire\Commerce\Favorites::FAVORITES), quindi i
 * preferiti persistenti referenziano righe reali del catalogo (slug → famiglia):
 *  - puppy-yoga-milano        → event   (card XD "Puppy Yoga, Milano — LUN 30 MAG 15:30", match esatto)
 *  - pomeriggio-addestramento → event   (card XD "Pomeriggio di addestramento, Milano", match esatto)
 *  - vacanza-montagna         → activity (card XD "Vacanza di relax in montagna, Alpi — 5 giorni", match esatto)
 *  - hotel-brescia            → structure (per la card XD "Hotel con piscina sul lago" — stesso rating 4,5)
 *  - dog-sitting              → service  (nessuna card servizio in XD; copre la famiglia)
 *  - relax-lombardia-2        → smartbox_package (card XD "Weekend di relax in Lombardia", tipo wellness)
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $giulia = User::updateOrCreate(['email' => 'giulia.rossi@gmail.com'], [
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'birth_date' => '1998-03-22',
            'phone' => '340 5738920',
            'address' => 'Viale Abruzzi 20',
            'city' => 'Milano',
            'postal_code' => '20131',
            'password' => 'password',
        ]);
        $giulia->syncRoles(['client']);
        $giulia->pets()->updateOrCreate(['species' => 'Cane'], []);

        $this->seedFavorites($giulia);

        $partner = User::updateOrCreate(['email' => 'partner@animalamo.test'], [
            'first_name' => 'Susanna',
            'last_name' => 'Rossi',
            'phone' => '349 8798828',
            'password' => 'password',
            'is_active' => true,
        ]);
        $partner->syncRoles(['partner']);

        // Dati fiscali + pagamento dal mock XD "Profilo - info personali/metodo di pagamento".
        $partner->partnerProfile()->updateOrCreate([], [
            'business_name' => 'Hotel Rosovino Milano',
            'vat' => '86334519757',
            'tax_code' => 'SSNNRSS98A39T582I',
            'pec' => 'susanna.rossi@pec.it',
            'sdi' => 'SUBM70N',
            'address' => 'Via C. Pacini, 19',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20131',
            'account_holder' => 'Susanna Rossi',
            'iban' => 'IT037400000000007382',
            'bic' => 'UNCRITMM',
        ]);

        $this->seedServices($partner);
    }

    /** Servizi completati del partner demo (card XD "I miei servizi"). */
    private function seedServices(User $partner): void
    {
        $services = [
            [
                'service_category' => 'struttura', 'type' => 'hotel', 'name' => 'Hotel Brescia',
                'address' => 'Via Terme 12', 'city' => 'Darfo Boario Terme', 'province' => 'BS', 'zip' => '25047',
                'license' => 'LIC-2024-BS-118',
                'description' => 'Hotel pet-friendly immerso nel verde delle Terme di Boario.',
                'rooms' => [['type' => 'Doppia', 'count' => 8, 'price' => '90'], ['type' => 'Suite', 'count' => 2, 'price' => '160']],
                'checkin_from' => '14:00', 'checkin_to' => '20:00', 'checkout_from' => '08:00', 'checkout_to' => '11:00',
                'cancellation_when' => '7',
                'services' => ['wifi', 'piscina', 'parcheggio'], 'additional_services' => ['colazione'], 'rules' => ['vietato_fumare'],
                'animal_services' => ['area_animali', 'servizio_veterinario'], 'animal_services_other' => 'Ciotole e cuccia in camera',
                'account_holder' => 'Susanna Rossi', 'iban' => 'IT60X0542811101000000123456', 'bic' => 'UNCRITMM', 'sdi' => 'SUBM70N',
                'current_step' => 11,
            ],
            [
                'service_category' => 'attivita', 'type' => 'attivita', 'name' => 'Puppy Yoga',
                'city' => 'Milano', 'province' => 'MI', 'meeting_point' => 'Parco Sempione, ingresso Arco della Pace',
                'description' => 'Sessione di yoga con cuccioli di cane.',
                'date_start' => '2026-05-30', 'time_start' => '15:30',
                'current_step' => 10,
            ],
            [
                'service_category' => 'smartbox', 'type' => 'soggiorno', 'name' => 'Weekend di relax in Lombardia',
                'city' => 'Como', 'province' => 'CO',
                'description' => 'Un weekend di benessere per te e il tuo animale.',
                'price' => '199', 'duration_days' => 2,
                'current_step' => 12,
            ],
        ];

        foreach ($services as $data) {
            StructureDraft::updateOrCreate(
                ['user_id' => $partner->id, 'name' => $data['name']],
                array_merge($data, ['user_id' => $partner->id, 'status' => StructureDraft::STATUS_COMPLETED]),
            );
        }
    }

    /** Le 6 card preferiti (mapping slug → famiglia nel docblock della classe). */
    private function seedFavorites(User $giulia): void
    {
        $targets = [
            // Slug struttura duplicati nel mock: orderBy(position) rende la scelta deterministica.
            ['structure', Structure::where('slug', 'hotel-brescia')->orderBy('position')->firstOrFail()->id],
            ['structure', Structure::where('slug', 'dog-sitting')->orderBy('position')->firstOrFail()->id],
            ['event', Event::where('slug', 'puppy-yoga-milano')->firstOrFail()->id],
            ['event', Event::where('slug', 'vacanza-montagna')->firstOrFail()->id],
            ['event', Event::where('slug', 'pomeriggio-addestramento')->firstOrFail()->id],
            ['smartbox_package', SmartboxPackage::where('slug', 'relax-lombardia-2')->firstOrFail()->id],
        ];

        foreach ($targets as [$type, $id]) {
            $giulia->favorites()->firstOrCreate([
                'favoritable_type' => $type,
                'favoritable_id' => $id,
            ]);
        }
    }
}
