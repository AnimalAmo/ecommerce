<?php

namespace App\Console\Commands;

use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\FamilyPublisher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ricalcola i servizi (pivot amenityables) delle schede già pubblicate a
 * partire dalla loro bozza, senza ripubblicarle.
 *
 * Nasce dal 28/09/2026: il catalogo amenity ha sei voci nuove e
 * FamilyPublisher::AMENITY_MAP raggiunge tredici slug che prima ignorava (sette
 * voci che la cliente ha chiesto di rendere selezionabili il 26-27/09/2026,
 * sei slug che il partner spuntava e la scheda non mostrava, compreso il
 * campo_da_tennis della smartbox). Il pivot
 * però lo scrive solo il publisher, alla pubblicazione: senza questo comando
 * una struttura che aveva spuntato «TV» mesi fa non la mostrerebbe finché il
 * partner non ripubblica.
 *
 * Il calcolo è quello del publisher, FamilyPublisher::amenityPivot(), non una
 * copia. Ripubblicare invece non si può: rimetterebbe in moderazione, riscriverebbe
 * slug, foto e testi, e potrebbe potare una copertina. Qui si tocca solo il
 * pivot.
 *
 * COSA TOCCA. Ogni riga a catalogo con `structure_draft_id`, anche nascosta,
 * sospesa, ritirata o in attesa di moderazione (withHidden): il publisher le
 * aggiorna tutte allo stesso modo. Restano fuori:
 *   - le schede del catalogo demo, che non hanno una bozza e le cui voci le
 *     ha scelte a mano il seeder;
 *   - la riga di una famiglia diversa da quella attuale della bozza: è ciò che
 *     resta di un cambio di ramo (ritirata perché ha prenotazioni future), il
 *     publisher non la aggiorna più e le colonne che le servivano la bozza le
 *     ha azzerate.
 *
 * DA SAPERE. La bozza è la versione più recente di ciò che il partner ha
 * dichiarato, anche se sta ancora modificando il servizio e non ha finito il
 * wizard: il comando porta sulla scheda i servizi che vede nella bozza in quel
 * momento. È lo stesso che farebbe la ripubblicazione a fine modifica, un
 * passo prima.
 *
 * Idempotente: una scheda già allineata non si scrive, e un secondo giro non
 * trova niente da cambiare.
 */
class ResyncAmenitiesCommand extends Command
{
    protected $signature = 'animalamo:resync-amenities
        {--dry-run : Non scrive: dice quante schede e quante righe del pivot cambierebbero}';

    protected $description = 'Ricalcola i servizi delle schede pubblicate dalla loro bozza, senza ripubblicarle';

    /**
     * Per ogni riga a catalogo: la famiglia della bozza che la pubblica e le
     * colonne della bozza che il suo publisher passa a syncAmenities().
     *
     * Sono le stesse di StructurePublisher, EventPublisher e SmartboxPublisher,
     * che le scrivono in linea: se un publisher cambia le colonne, va cambiato
     * anche qui, o il comando ricalcola una scheda diversa da quella che la
     * ripubblicazione produrrebbe.
     */
    private const SOURCES = [
        Structure::class => [
            'label' => 'struttura',
            'family' => 'struttura',
            'columns' => ['services', 'additional_services', 'animal_services'],
        ],
        Event::class => [
            'label' => 'attività/evento',
            'family' => 'attivita',
            'columns' => ['services', 'additional_services', 'animal_services'],
        ],
        SmartboxPackage::class => [
            'label' => 'smartbox',
            'family' => 'smartbox',
            'columns' => ['included_services', 'additional_services', 'meals', 'animal_services'],
        ],
    ];

    public function handle(): int
    {
        $missing = $this->missingAmenities();

        if ($missing !== []) {
            $this->components->error('Mancano a catalogo: '.implode(', ', $missing).'. Lancia prima le migrazioni (2026_09_28_140001).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $names = Amenity::query()->pluck('name', 'id');

        $seen = 0;
        $skipped = 0;
        $pivotRows = 0;
        /** @var list<array{0: string, 1: int, 2: int, 3: string, 4: string, 5: string}> $changed */
        $changed = [];

        foreach (self::SOURCES as $model => $source) {
            $model::withHidden()
                ->whereNotNull('structure_draft_id')
                ->with(['draft', 'amenities'])
                ->chunkById(100, function (EloquentCollection $rows) use ($source, $dryRun, $names, &$seen, &$skipped, &$pivotRows, &$changed): void {
                    foreach ($rows as $row) {
                        $seen++;
                        $draft = $row->draft;

                        if (! $draft instanceof StructureDraft || $draft->family() !== $source['family']) {
                            $skipped++;

                            continue;
                        }

                        $target = FamilyPublisher::amenityPivot($this->selectedSlugs($draft, $source['columns']));
                        $diff = $this->diff($this->currentPivot($row), $target);

                        if ($diff['rows'] === 0) {
                            continue;
                        }

                        if (! $dryRun) {
                            DB::transaction(fn () => $row->amenities()->sync($target));
                        }

                        $pivotRows += $diff['rows'];
                        $changed[] = [
                            $source['label'],
                            $row->getKey(),
                            $draft->getKey(),
                            (string) $row->slug,
                            $this->nameList($diff['shown'], $names),
                            $this->nameList($diff['hidden'], $names),
                        ];
                    }
                });
        }

        $this->report($changed, $seen, $skipped, $pivotRows, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Voci che AMENITY_MAP nomina e il catalogo non ha. Con anche una sola
     * mancante il comando non gira: uno slug senza la sua riga non finisce nel
     * pivot, e la scheda perderebbe in silenzio proprio il servizio per cui il
     * comando esiste.
     *
     * @return list<string>
     */
    private function missingAmenities(): array
    {
        $expected = array_values(array_unique(FamilyPublisher::AMENITY_MAP));

        return array_values(array_diff($expected, Amenity::query()->whereIn('name', $expected)->pluck('name')->all()));
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function selectedSlugs(StructureDraft $draft, array $columns): array
    {
        return array_merge(...array_map(fn (string $column): array => array_values((array) ($draft->{$column} ?? [])), $columns));
    }

    /**
     * Il pivot com'è adesso, nella stessa forma del payload di sync().
     *
     * @return array<int, array{included: bool, position: int}>
     */
    private function currentPivot(Model $row): array
    {
        return $row->amenities
            ->mapWithKeys(fn (Amenity $amenity): array => [$amenity->id => [
                'included' => (bool) $amenity->pivot->included,
                'position' => (int) $amenity->pivot->position,
            ]])
            ->all();
    }

    /**
     * Quante righe del pivot sync() scriverebbe (aggiunte, tolte, cambiate) e,
     * fra queste, quali voci compaiono o spariscono dalla scheda. Un cambio di
     * sola posizione conta fra le righe ma non si vede in elenco.
     *
     * @param  array<int, array{included: bool, position: int}>  $current
     * @param  array<int, array{included: bool, position: int}>  $target
     * @return array{rows: int, shown: list<int>, hidden: list<int>}
     */
    private function diff(array $current, array $target): array
    {
        $rows = count(array_diff_key($current, $target));
        $shown = [];
        $hidden = [];

        foreach ($target as $id => $wanted) {
            $now = $current[$id] ?? null;

            if ($now !== $wanted) {
                $rows++;
            }

            $wasShown = $now['included'] ?? false;

            if ($wanted['included'] && ! $wasShown) {
                $shown[] = $id;
            } elseif (! $wanted['included'] && $wasShown) {
                $hidden[] = $id;
            }
        }

        // Voci che il pivot ha e il catalogo non ha più: sync() le stacca.
        foreach (array_diff_key($current, $target) as $id => $was) {
            if ($was['included']) {
                $hidden[] = $id;
            }
        }

        return ['rows' => $rows, 'shown' => $shown, 'hidden' => $hidden];
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int, string>  $names
     */
    private function nameList(array $ids, Collection $names): string
    {
        return $ids === [] ? '—' : implode(', ', array_map(fn (int $id): string => (string) ($names[$id] ?? "#{$id}"), $ids));
    }

    /**
     * Il conteggio e l'elenco insieme: il conteggio dice se il giro è servito,
     * l'elenco dice a chi (come in animalamo:connect-sync). La simulazione
     * stampa le stesse righe del giro vero, al condizionale.
     *
     * @param  list<array{0: string, 1: int, 2: int, 3: string, 4: string, 5: string}>  $changed
     */
    private function report(array $changed, int $seen, int $skipped, int $pivotRows, bool $dryRun): void
    {
        if ($dryRun) {
            $this->components->warn('Simulazione: nessuna scrittura.');
        }

        $verb = $dryRun ? 'che cambierebbero' : 'risincronizzate';

        $this->components->info(sprintf(
            'Schede %s: %d su %d con una bozza (righe del pivot: %d). Saltate: %d.',
            $verb,
            count($changed),
            $seen,
            $pivotRows,
            $skipped,
        ));

        if ($skipped > 0) {
            $this->line('  Saltate: righe senza bozza o di una famiglia diversa da quella attuale della bozza (resti di un cambio di ramo).');
        }

        if ($changed === []) {
            $this->line('  Nessuna scheda da cambiare: i servizi a catalogo coincidono già con le bozze.');

            return;
        }

        $this->table(
            ['tipo', 'id', 'bozza', 'slug', $dryRun ? 'comparirebbero' : 'compaiono', $dryRun ? 'sparirebbero' : 'spariscono'],
            $changed,
        );
    }
}
