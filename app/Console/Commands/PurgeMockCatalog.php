<?php

namespace App\Console\Commands;

use App\Models\Community\CommunityPost;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;

/**
 * Toglie dal database il catalogo campione dell'XD e le persone demo.
 *
 * Serve una volta sola, alla messa online: animalamo.it nasce rinominando il
 * sito di staging, quindi si porta dietro il suo database. Il seeder non
 * ricrea più mock e utenti demo (vedi DatabaseSeeder), ma non cancella quelli
 * già seminati — e finché ci sono, il sito vende dodici strutture inventate e
 * tiene aperto un account partner con password nota.
 *
 * Il discrimine fra mock e contenuto vero è `user_id`: i publisher dei partner
 * lo scrivono sempre (StructurePublisher, EventPublisher, SmartboxPublisher),
 * i seeder del mock mai. Nel dubbio il comando conserva: cancella solo righe
 * senza proprietario.
 */
class PurgeMockCatalog extends Command
{
    use ConfirmableTrait;

    protected $signature = 'animalamo:purge-mock-catalog {--force : Esegue senza chiedere conferma}';

    protected $description = "Cancella il catalogo campione dell'XD, le persone demo e i post finti della community";

    /** Le persone del mock XD, per indirizzo: le crea DemoUserSeeder con password "password". */
    private const DEMO_EMAILS = [
        'giulia.rossi@gmail.com',
        'partner@animalamo.test',
        'susanna.rossi@pec.it',
    ];

    /** Alias della morph map (AppServiceProvider) → model del catalogo acquistabile. */
    private const CATALOG = [
        'structure' => Structure::class,
        'event' => Event::class,
        'smartbox_package' => SmartboxPackage::class,
    ];

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $demoUsers = User::whereIn('email', self::DEMO_EMAILS)->pluck('id')->all();

        // Senza proprietario (il mock dell'XD) oppure intestate a una persona
        // demo: DemoUserSeeder assegna al partner finto alcuni servizi del
        // mock, e cancellando lui resterebbero orfani invece che spariti.
        $mock = [];
        foreach (self::CATALOG as $alias => $model) {
            $mock[$alias] = $model::query()
                ->whereNull('user_id')
                ->when($demoUsers !== [], fn ($query) => $query->orWhereIn('user_id', $demoUsers))
                ->pluck('id')
                ->all();
        }

        DB::transaction(function () use ($mock, $demoUsers): void {
            foreach ($mock as $alias => $ids) {
                if ($ids === []) {
                    continue;
                }

                // Tabelle polimorfiche senza vincolo di chiave esterna: nessuna
                // cascade le tocca, resterebbero appese a id inesistenti.
                foreach (['reviews' => 'reviewable', 'faqs' => 'faqable', 'amenityables' => 'amenityable', 'cart_items' => 'purchasable', 'favorites' => 'favoritable'] as $table => $morph) {
                    DB::table($table)
                        ->where($morph.'_type', $alias)
                        ->whereIn($morph.'_id', $ids)
                        ->delete();
                }

                // order_items è uno snapshot: titolo e prezzo restano, così la
                // storia dell'ordine regge anche senza il prodotto. Si sgancia
                // solo il riferimento, che altrimenti punterebbe nel vuoto.
                DB::table('order_items')
                    ->where('purchasable_type', $alias)
                    ->whereIn('purchasable_id', $ids)
                    ->update(['purchasable_type' => null, 'purchasable_id' => null]);

                self::CATALOG[$alias]::whereIn('id', $ids)->delete();
            }

            // I post veri passano da CommunityService, che scrive sempre
            // l'autore: quelli senza user_id sono del seeder. Le risposte
            // scendono in cascata con il post.
            CommunityPost::whereNull('user_id')
                ->when($demoUsers !== [], fn ($query) => $query->orWhereIn('user_id', $demoUsers))
                ->delete();

            User::whereIn('id', $demoUsers)->delete();

            // Conteggio congelato del mock: i badge ora contano davvero, ma la
            // colonna resterebbe a dire 158 strutture.
            Region::query()->update(['structures_count' => 0]);
        });

        $this->components->info(sprintf(
            'Rimossi: %d strutture, %d eventi, %d smartbox, %d utenti demo.',
            count($mock['structure']),
            count($mock['event']),
            count($mock['smartbox_package']),
            count($demoUsers),
        ));

        return self::SUCCESS;
    }
}
