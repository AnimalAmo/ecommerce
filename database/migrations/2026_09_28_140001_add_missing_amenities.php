<?php

use App\Models\Amenity\Amenity;
use Database\Seeders\AmenitySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Le voci che il catalogo amenity aveva perso per strada.
     *
     * Sei slug del wizard erano spuntabili senza una voce a catalogo
     * (riscaldamento, ricarica_elettrica, tv, piscina, area_animali e il
     * campo_da_tennis della smartbox): il
     * partner li indicava e la scheda non li mostrava mai. Dal 28/09/2026
     * AmenitySeeder le ha, e FamilyPublisher::AMENITY_MAP le raggiunge insieme
     * alle sette voci che la cliente ha chiesto di rendere selezionabili
     * (26-27/09/2026). Il seeder però al deploy non gira: questa migrazione
     * porta le voci nuove nel database del cliente.
     *
     * La lista è quella del seeder (AmenitySeeder::upsertCatalog()), non una
     * copia: updateOrCreate per nome, quindi le quattordici già presenti non
     * cambiano e rilanciarla non crea doppioni.
     *
     * Un catalogo vuoto non si tocca: è un database appena creato (un'istanza
     * nuova, la suite dei test) e lo riempie il seeder. Popolarlo qui farebbe
     * trovare le amenity a chi non le ha chieste, e un test che ne crea una con
     * la factory urterebbe l'indice unico sul nome.
     *
     * Le schede già pubblicate non cambiano da sole: il pivot si ricalcola
     * quando il partner ripubblica, o con `animalamo:resync-amenities`.
     */
    public function up(): void
    {
        if (Amenity::query()->doesntExist()) {
            return;
        }

        $created = AmenitySeeder::upsertCatalog();

        if ($created !== []) {
            Log::info('Catalogo amenity: voci aggiunte', ['names' => $created]);
        }
    }

    /**
     * Toglie le sei voci aggiunte, ma solo quelle che nessuna scheda mostra.
     *
     * Dopo una ripubblicazione o un resync ogni scheda ha una riga di pivot per
     * ogni voce del catalogo, quasi tutte con included=false: quelle si
     * possono perdere, la scheda non le mostra. Una riga con included=true
     * invece è un servizio che il partner ha dichiarato e il cliente vede, e
     * la voce resta. Il log dice quali, perché è l'unico momento in cui si sa.
     *
     * L'elenco è scritto qui e non letto dal seeder: `down()` deve disfare
     * quello che questa migrazione ha aggiunto il 28/09/2026, non quello che
     * il seeder elencherà domani. Le sette voci della cliente c'erano già e
     * non si toccano.
     */
    public function down(): void
    {
        $added = ['Riscaldamento', 'Ricarica auto elettriche', 'TV', 'Piscina', 'Campo da tennis', 'Area dedicata agli animali'];

        $kept = [];

        foreach (Amenity::query()->whereIn('name', $added)->get() as $amenity) {
            $shown = DB::table('amenityables')
                ->where('amenity_id', $amenity->id)
                ->where('included', true)
                ->exists();

            if ($shown) {
                $kept[] = $amenity->name;

                continue;
            }

            // Le righe included=false esplicitamente, senza contare sulla
            // cascade: su SQLite i vincoli dipendono da come è aperta la
            // connessione.
            DB::transaction(function () use ($amenity): void {
                DB::table('amenityables')->where('amenity_id', $amenity->id)->delete();
                $amenity->delete();
            });
        }

        if ($kept !== []) {
            Log::warning('Catalogo amenity: voci tenute al rollback perché una scheda le mostra', ['names' => $kept]);
        }
    }
};
