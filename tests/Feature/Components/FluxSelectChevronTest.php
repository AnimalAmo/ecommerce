<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Una `flux:select` ha già la sua freccia: Flux la disegna come background-image
 * (`select[data-flux-select-native]` in flux.css), dentro o fuori da un field.
 * `appearance-auto` riaccende anche quella nativa e il select ne mostra due.
 * Fino al 29/09/2026 il CLAUDE.md lo consigliava, e tre select lo avevano: il
 * prefisso del telefono (otto pagine, «Lavora con noi» compresa) e due filtri
 * dell'admin. La prova guarda i sorgenti, così vale anche per le viste che
 * nessun altro test disegna.
 *
 * Tutto il file e non il solo tag: le pagine passano le classi dei select con
 * variabili (`$selectClass` in @php, `:select-class` di x-phone-input), e dentro
 * il tag si legge `{{ $selectClass }}`, non il valore (review del 29/09/2026:
 * con `!appearance-auto` in `$selectClass` la prova per tag restava verde). Si
 * tolgono solo i commenti Blade, che la citano per spiegare il divieto. Se un
 * giorno servisse davvero `appearance-auto` su un elemento che non è una
 * flux:select, questa prova va ristretta a quel caso.
 */
class FluxSelectChevronTest extends TestCase
{
    public function test_no_flux_select_turns_the_native_arrow_back_on(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents());

            if (str_contains($source, 'appearance-auto')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'appearance-auto in una vista: su una flux:select dà due frecce nello stesso select.');
    }
}
