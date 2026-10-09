<?php

namespace App\Console\Commands;

use App\Models\Structure\Structure;
use App\Services\Admin\Catalog\StructureMerger;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Accorpa le strutture create «una per camera» in una struttura con stanze
 * (vedi {@see StructureMerger}). Caso reale: «Il Casale Sotto le Stelle -
 * Monia / - Dona / - Stella», tre schede per tre camere dello stesso casale.
 *
 * Prima con --dry-run: stampa le stanze che nascerebbero e cosa si sposta,
 * senza scrivere. Il nome del target va poi ripulito a mano dal pannello
 * (es. «Il Casale Sotto le Stelle»).
 */
class MergeStructures extends Command
{
    protected $signature = 'catalog:merge-structures
        {target : Id della struttura che resta (riceve le stanze)}
        {sources* : Id delle strutture da trasformare in stanze del target}
        {--dry-run : Mostra cosa farebbe, senza scrivere}';

    protected $description = 'Accorpa strutture create una per camera in una struttura con stanze (301 dalle vecchie URL)';

    public function handle(StructureMerger $merger): int
    {
        $ids = array_map('intval', [(string) $this->argument('target'), ...(array) $this->argument('sources')]);
        $found = Structure::withHidden()->with('draft')->whereKey($ids)->get()->keyBy('id');

        $missing = array_diff($ids, $found->keys()->all());

        if ($missing !== []) {
            $this->error('Strutture inesistenti: #'.implode(', #', $missing).'. Niente fatto.');

            return self::FAILURE;
        }

        $target = $found[$ids[0]];
        /** @var Collection<int, Structure> $sources */
        $sources = collect(array_slice($ids, 1))->map(fn (int $id): Structure => $found[$id]);

        if ($errors = $merger->errors($target, $sources)) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->error('Niente fatto.');

            return self::FAILURE;
        }

        $this->describe($merger, $target, $sources);

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: niente scritto.');

            return self::SUCCESS;
        }

        try {
            $structure = $merger->merge($target, $sources);
        } catch (Throwable $exception) {
            $this->error('Accorpamento annullato, niente scritto: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Fatto: la struttura #{$structure->id} ha {$structure->rooms()->count()} stanze. Ricorda di ripulirne il nome dal pannello.");

        return self::SUCCESS;
    }

    /** @param  Collection<int, Structure>  $sources */
    private function describe(StructureMerger $merger, Structure $target, Collection $sources): void
    {
        $this->line("Target #{$target->id} «{$target->getTranslation('name', 'it')}» (bozza #{$target->structure_draft_id}, partner utente {$target->user_id})");

        if ($rename = $merger->targetRoomName($target)) {
            $this->line("La stanza già presente nel target, senza nome, si chiamerà «{$rename['it']}».");
        }

        $bookings = $merger->targetBookings($target);

        if ($bookings['ambiguous'] > 0) {
            $this->warn("Attenzione: {$bookings['ambiguous']} righe ordine del target restano senza stanza (la bozza ha più righe e nessuna stanza pubblicata): l'occupazione non le conterà.");
        }

        $targetRow = ['#'.$target->id.' (target)', $rename ? 'it: '.$rename['it'] : '(stanze attuali)', '', '', '', '', '', '', '', $bookings['order_items']];

        $this->table(
            ['Sorgente', 'Stanza', 'Tipo', 'Prezzo/notte', 'Unità', 'Ospiti', 'Animali', 'Foto', 'Recensioni', 'Righe ordine'],
            [$targetRow, ...collect($merger->plan($target, $sources))->map(fn (array $plan): array => [
                '#'.$plan['source_id'],
                collect($plan['row']['name'])->map(fn (string $name, string $locale): string => "{$locale}: {$name}")->implode(' / '),
                $plan['row']['type'],
                $plan['row']['price'],
                $plan['row']['units'],
                $plan['row']['max_guests'],
                $plan['row']['max_animals'],
                count($plan['row']['photos']),
                $plan['reviews'],
                $plan['order_items'],
            ])->all()],
        );

        $this->line('Le sorgenti verranno sospese e le loro URL reindirizzate (301) al target.');
    }
}
