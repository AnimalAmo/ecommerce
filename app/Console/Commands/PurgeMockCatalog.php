<?php

namespace App\Console\Commands;

use App\Models\Community\CommunityPost;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Models\Venue\Venue;
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
 * COSA CONTA COME MOCK. Non basta `user_id IS NULL`: il wizard partner è stato
 * pubblico fino al 08/09/2026 e in quella finestra salvava le bozze da ospite,
 * quindi esiste contenuto vero pubblicato con user_id nullo. Il discrimine
 * affidabile è `structure_draft_id`: i tre publisher lo usano come chiave di
 * updateOrCreate, i seeder del mock non lo scrivono mai. Il comando cancella
 * solo righe che NON hanno una bozza dietro e che non appartengono a un
 * partner vero. Nel dubbio conserva.
 *
 * COSA NON FA, di proposito: non tocca gli account registrati su staging fuori
 * dai due indirizzi demo, le candidature "Lavora con noi", i messaggi di
 * "Contattaci", i post scritti da persone reali, le bozze non demo e le foto
 * su disco. Sono righe che possono appartenere a un partner o a un cliente
 * vero: la lista delle query per rivederle a mano sta nel runbook
 * (docs/specs/2026-09-08-go-live-animalamo-it.md § 3.4).
 */
class PurgeMockCatalog extends Command
{
    use ConfirmableTrait;

    protected $signature = 'animalamo:purge-mock-catalog
        {--dry-run : Conta ciò che verrebbe cancellato e si ferma}
        {--force : Esegue senza chiedere conferma}';

    protected $description = "Cancella il catalogo campione dell'XD, le persone demo e i post finti della community";

    /**
     * Gli account creati da DemoUserSeeder. NON contiene susanna.rossi@pec.it:
     * quella è la PEC nel profilo del partner demo, non l'email di un utente —
     * cercarla fra le email potrebbe solo agganciare l'account di una persona
     * vera e portarsi via i suoi servizi.
     */
    private const DEMO_EMAILS = [
        'giulia.rossi@gmail.com',
        'partner@animalamo.test',
    ];

    /** Partita IVA del profilo demo: ritrova l'account anche se su staging gli hanno cambiato email. */
    private const DEMO_VAT = '86334519757';

    /** Alias della morph map (AppServiceProvider) → model del catalogo acquistabile. */
    private const CATALOG = [
        'structure' => Structure::class,
        'event' => Event::class,
        'smartbox_package' => SmartboxPackage::class,
    ];

    /** Tabelle polimorfiche senza vincolo di chiave esterna: nessuna cascade le tocca. */
    private const MORPH_TABLES = [
        'reviews' => 'reviewable',
        'faqs' => 'faqable',
        'amenityables' => 'amenityable',
        'cart_items' => 'purchasable',
        'favorites' => 'favoritable',
    ];

    public function handle(): int
    {
        $demoUsers = $this->demoUserIds();
        $mock = $this->mockCatalogIds($demoUsers);

        $this->report($mock, $demoUsers);

        if ($this->option('dry-run')) {
            $this->components->warn('Nessuna riga cancellata: --dry-run.');

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($mock, $demoUsers): void {
            foreach ($mock as $alias => $ids) {
                if ($ids === []) {
                    continue;
                }

                foreach (self::MORPH_TABLES as $table => $morph) {
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

            // Gli ordini demo: orders.user_id è nullOnDelete, quindi cancellare
            // la persona li lascerebbe orfani con dentro il suo nome, il suo
            // indirizzo e 1.311 € di fatturato inventato. Items e pagamenti
            // scendono in cascata.
            if ($demoUsers !== []) {
                Order::whereIn('user_id', $demoUsers)->delete();
                StructureDraft::whereIn('user_id', $demoUsers)->delete();
            }

            // I post veri passano da CommunityService, che scrive sempre
            // l'autore: quelli senza user_id sono del seeder. Le risposte
            // scendono in cascata con il post.
            CommunityPost::whereNull('user_id')
                ->when($demoUsers !== [], fn ($query) => $query->orWhereIn('user_id', $demoUsers))
                ->delete();

            // Uno per uno e non in massa: la delete del query builder non fa
            // scattare l'hook di spatie, e model_has_roles — che non ha chiave
            // esterna verso users — resterebbe con righe orfane. Se un giorno
            // l'AUTO_INCREMENT ripartisse, quel ruolo partner finirebbe addosso
            // a chi si registra.
            User::whereIn('id', $demoUsers)->get()->each->delete();

            // I due luoghi del mock ("Cascina Brescia", "Hotel Miramare"): la
            // cascade va nel verso opposto (events.venue_id è nullOnDelete),
            // quindi cancellare gli eventi non se li porta via. Quelli veri
            // nascono sempre con una bozza dietro.
            Venue::whereNull('structure_draft_id')->delete();

            // Conteggio congelato del mock: i badge ora contano davvero, ma la
            // colonna resterebbe a dire 158 strutture. Attenzione: RegionSeeder
            // la riscrive a ogni db:seed, quindi la pulizia va DOPO il seed.
            Region::query()->update(['structures_count' => 0]);
        });

        $this->components->info('Pulizia completata.');

        return self::SUCCESS;
    }

    /** @return list<int> */
    private function demoUserIds(): array
    {
        return User::query()
            ->whereIn('email', self::DEMO_EMAILS)
            ->orWhereHas('partnerProfile', fn ($query) => $query->where('vat', self::DEMO_VAT))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $demoUsers
     * @return array<string, list<int>>
     */
    private function mockCatalogIds(array $demoUsers): array
    {
        $mock = [];

        foreach (self::CATALOG as $alias => $model) {
            $mock[$alias] = $model::query()
                // Mai una bozza dietro: è la firma dei seeder, i publisher la
                // scrivono sempre. È questo a proteggere il contenuto vero
                // pubblicato quando il wizard era ancora aperto agli ospiti.
                ->whereNull('structure_draft_id')
                ->where(fn ($query) => $query
                    ->whereNull('user_id')
                    ->when($demoUsers !== [], fn ($owned) => $owned->orWhereIn('user_id', $demoUsers)))
                ->pluck('id')
                ->all();
        }

        return $mock;
    }

    /**
     * @param  array<string, list<int>>  $mock
     * @param  list<int>  $demoUsers
     */
    private function report(array $mock, array $demoUsers): void
    {
        $this->components->twoColumnDetail('Strutture mock', (string) count($mock['structure']));
        $this->components->twoColumnDetail('Eventi mock', (string) count($mock['event']));
        $this->components->twoColumnDetail('Smartbox mock', (string) count($mock['smartbox_package']));
        $this->components->twoColumnDetail('Utenti demo', (string) count($demoUsers));

        foreach (User::whereIn('id', $demoUsers)->pluck('email') as $email) {
            $this->components->twoColumnDetail('  account', $email);
        }

        // Il contenuto pubblicato da chi ha provato il wizard su staging ha una
        // bozza dietro: sopravvive, ed è giusto così, ma va guardato a mano.
        $published = collect(self::CATALOG)
            ->map(fn (string $model) => $model::whereNotNull('structure_draft_id')->count())
            ->sum();

        if ($published > 0) {
            $this->components->warn("Restano {$published} righe di catalogo con una bozza dietro: revisionarle a mano (runbook § 3.4).");
        }
    }
}
