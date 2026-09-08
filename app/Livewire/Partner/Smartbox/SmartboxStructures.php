<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Models\Structure\Structure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SmartboxStructures extends Component
{
    use InteractsWithStructureDraft;

    /** Strutture incluse nella smartbox (multi-scelta): id delle righe catalogo. */
    public array $structures = [];

    /**
     * Cache di richiesta: mount/next e render chiedono le stesse opzioni.
     * Privata, quindi fuori dal payload Livewire.
     *
     * @var array<string, array{name: string, city: string}>|null
     */
    private ?array $options = null;

    public function mount(): void
    {
        // La selezione salvata vale solo per le strutture ancora disponibili:
        // così spariscono da sola le vecchie chiavi demo del mockup (e le
        // strutture eliminate dal partner), che altrimenti farebbero fallire
        // la validazione su un checkbox che nessuno vede più.
        $this->structures = array_values(array_intersect(
            array_map(strval(...), $this->draft()->smartbox_structures ?? []),
            array_keys($this->options()),
        ));
    }

    /**
     * Gli id arrivano dal DOM come stringhe, ma un payload manomesso può
     * portare interi: senza normalizzare, il confronto stretto della view
     * lascerebbe "spenta" una card in realtà selezionata.
     */
    public function updatedStructures(): void
    {
        $this->structures = array_values(array_map(
            strval(...),
            array_filter($this->structures, fn (mixed $id): bool => is_string($id) || is_numeric($id)),
        ));
    }

    public function next(): void
    {
        $this->validate([
            'structures' => ['array'],
            // Le chiavi ammesse si ricalcolano qui, non si leggono dal payload:
            // il client può scrivere qualsiasi id, ma passano solo le strutture
            // del partner loggato.
            'structures.*' => [Rule::in(array_keys($this->options()))],
        ]);

        $this->saveStep(['smartbox_structures' => $this->structures], 10);
        $this->redirectRoute('partner.smartbox.photos');
    }

    /**
     * Strutture pubblicate dal partner loggato, selezionabili nel cofanetto.
     * Chiave = id della riga catalogo (stabile anche se il nome cambia).
     * Il `whereNotNull` non è ridondante: le righe del catalogo mock non hanno
     * proprietario e `where('user_id', null)` in SQL diventerebbe `IS NULL`,
     * offrendole a chiunque apra lo step.
     *
     * @return array<string, array{name: string, city: string}>
     */
    private function options(): array
    {
        return $this->options ??= Structure::query()
            ->whereNotNull('user_id')
            ->where('user_id', Auth::id())
            ->orderBy('position')
            ->get(['id', 'name', 'location'])
            ->mapWithKeys(fn (Structure $structure): array => [
                (string) $structure->id => [
                    'name' => (string) $structure->name,
                    'city' => (string) $structure->location,
                ],
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-structures', ['options' => $this->options()])
            ->title(__('partner.smartbox_structures.title'));
    }
}
