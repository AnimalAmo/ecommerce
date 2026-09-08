<?php

namespace App\Livewire\Catalog;

use App\Models\Region\Region;
use App\Models\Structure\Structure;
use Livewire\Attributes\Url;
use Livewire\Component;

class AnimalHoliday extends Component
{
    /** Filtro "Dove": deep-linkabile (?dove=…); la hero home ci reindirizza con ?dove. */
    #[Url(as: 'dove')]
    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // La ricerca "Dove" filtra le regioni in render(): nessuna paginazione qui.
        // Il "Quando"/ospiti/animali non filtrano ancora (step 6 disponibilità).
    }

    public function render()
    {
        $term = trim($this->where);

        return view('livewire.catalog.animal-holiday', [
            // Ordine di griglia XD (riga per riga), non alfabetico. "Dove" restringe per nome
            // regione (LIKE %dove%, case-insensitive). Zero risultati → messaggio nel template
            // ("Nessuna località trovata"; il fallback con alternative simili arriva allo step 6).
            //
            // Le 20 regioni restano tutte in griglia anche a catalogo vuoto: sono dati veri
            // (non mock), sono la sola strada verso la pagina regione e nascondere quelle
            // senza strutture lascerebbe, il giorno 1, una pagina bianca col messaggio
            // "Nessuna località trovata" — cioè una ricerca fallita che nessuno ha fatto.
            // A mentire era il badge: il conteggio ora è withCount sulle strutture davvero
            // pubblicate (la colonna structures_count è ferma ai numeri dell'artboard) e
            // sotto zero il badge non si disegna affatto.
            'regions' => Region::query()
                // Alias esplicito: senza, withCount collide con la colonna omonima del mock.
                ->withCount(['structures as published_structures_count'])
                ->when($term !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes($term, '\%_').'%'))
                ->orderBy('position')
                ->get(),
            // Catalogo ancora vuoto: la griglia di regioni da sola prometterebbe schede che
            // non esistono, quindi il template ci mette sopra una riga onesta e una CTA.
            'catalogueEmpty' => Structure::query()->doesntExist(),
        ])->title(__('holiday.meta_title'));
    }
}
