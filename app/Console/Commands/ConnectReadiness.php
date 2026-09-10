<?php

namespace App\Console\Commands;

use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Censimento prima del passaggio a Stripe Connect in live.
 *
 * Risponde a due domande, entrambe bloccanti:
 *  - quali prodotti non hanno un titolare, e quindi non sono acquistabili
 *    (senza account connesso non esiste un conto su cui far nascere l'incasso);
 *  - quali partner hanno prodotti a catalogo ma non possono ancora incassare.
 *
 * Il discrimine fra mock e contenuto vero è la bozza: i tre publisher scrivono
 * sempre `structure_draft_id`, i seeder mai. Una riga orfana CON bozza è roba
 * pubblicata davvero nella finestra in cui il wizard partner era aperto agli
 * ospiti (fino al 08/09/2026), e va recuperata, non cancellata.
 */
class ConnectReadiness extends Command
{
    protected $signature = 'animalamo:connect-readiness
        {--assign-from-drafts : Assegna il proprietario alle righe la cui bozza lo conosce}';

    protected $description = 'Censisce prodotti senza titolare e partner non ancora collegati a Stripe';

    /** Alias morph → model del catalogo acquistabile. */
    private const CATALOG = [
        'structures' => Structure::class,
        'events' => Event::class,
        'smartbox_packages' => SmartboxPackage::class,
    ];

    public function handle(): int
    {
        $this->reportCatalog();

        if ($this->option('assign-from-drafts')) {
            $this->assignFromDrafts();
        }

        $this->reportPartners();

        return self::SUCCESS;
    }

    private function reportCatalog(): void
    {
        $this->components->info('Prodotti a catalogo senza titolare');

        $rows = [];

        foreach (self::CATALOG as $table => $model) {
            $orphans = $model::query()->whereNull('user_id');

            $rows[] = [
                $table,
                $model::query()->count(),
                (clone $orphans)->count(),
                // Con bozza = pubblicato davvero: va recuperato.
                (clone $orphans)->whereNotNull('structure_draft_id')->count(),
                (clone $orphans)->whereIn('structure_draft_id', $this->ownedDraftIds())->count(),
                // Senza bozza = mock del seeder: si rimuove dal catalogo.
                (clone $orphans)->whereNull('structure_draft_id')->count(),
            ];
        }

        $this->table(['tabella', 'totale', 'orfani', 'di cui veri', 'recuperabili', 'mock'], $rows);

        $this->listRecoverable();
    }

    /** Le righe vere ancora senza titolare, una per una: vanno guardate. */
    private function listRecoverable(): void
    {
        $rows = [];

        foreach (self::CATALOG as $table => $model) {
            $model::query()
                ->whereNull('user_id')
                ->whereNotNull('structure_draft_id')
                ->get()
                ->each(function (Model $product) use (&$rows, $table): void {
                    $draft = StructureDraft::find($product->structure_draft_id);

                    $rows[] = [
                        $table,
                        $product->getKey(),
                        $product->slug ?? '—',
                        $draft?->user_id ?? '— (bozza senza utente)',
                    ];
                });
        }

        if ($rows === []) {
            $this->components->info('Nessun prodotto vero rimasto senza titolare.');

            return;
        }

        $this->components->warn('Prodotti pubblicati davvero e rimasti senza titolare:');
        $this->table(['tabella', 'id', 'slug', 'titolare dalla bozza'], $rows);
    }

    private function assignFromDrafts(): void
    {
        $owned = $this->ownedDraftIds();
        $assigned = 0;

        DB::transaction(function () use ($owned, &$assigned): void {
            foreach (self::CATALOG as $model) {
                $assigned += $model::query()
                    ->whereNull('user_id')
                    ->whereIn('structure_draft_id', $owned)
                    ->get()
                    ->each(fn (Model $product) => $product->update([
                        'user_id' => StructureDraft::find($product->structure_draft_id)->user_id,
                    ]))
                    ->count();
            }
        });

        $this->components->info("Prodotti riassegnati dal proprietario della bozza: {$assigned}.");
    }

    /** Id delle bozze che sanno di chi sono: le uniche da cui si può dedurre. */
    private function ownedDraftIds(): array
    {
        return StructureDraft::query()->whereNotNull('user_id')->pluck('id')->all();
    }

    private function reportPartners(): void
    {
        $this->components->info('Partner con prodotti a catalogo e onboarding Stripe incompleto');

        $rows = [];

        PartnerProfile::query()->with('user')->get()
            ->each(function (PartnerProfile $profile) use (&$rows): void {
                if ($profile->canBePaid()) {
                    return;
                }

                $published = collect(self::CATALOG)
                    ->sum(fn (string $model): int => $model::query()->where('user_id', $profile->user_id)->count());

                if ($published === 0) {
                    return;
                }

                $rows[] = [
                    $profile->business_name ?? '—',
                    $profile->user?->email ?? '—',
                    $published,
                    $profile->stripe_account_id ?? 'nessun account',
                    $profile->stripe_charges_enabled ? 'sì' : 'no',
                    $profile->stripe_payouts_enabled ? 'sì' : 'no',
                ];
            });

        if ($rows === []) {
            $this->components->info('Tutti i partner con prodotti a catalogo possono incassare.');

            return;
        }

        // Finché non completano l'onboarding i loro prodotti non sono vendibili.
        $this->components->warn('Questi partner hanno prodotti online ma non possono incassare:');
        $this->table(['ragione sociale', 'email', 'prodotti', 'account', 'incassi', 'bonifici'], $rows);
    }
}
