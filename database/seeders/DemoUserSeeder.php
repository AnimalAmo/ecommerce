<?php

namespace Database\Seeders;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
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
            'first_name' => 'Partner',
            'last_name' => 'Demo',
            'password' => 'password',
        ]);
        $partner->syncRoles(['partner']);
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
