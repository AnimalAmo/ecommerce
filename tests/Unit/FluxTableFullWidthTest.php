<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Richiesta di Matteo del 21/09/2026: ogni flux:table occupa tutta la
 * larghezza del suo contenitore.
 *
 * Flux lo fa da sé con `[:where(&)]:min-w-full`, che però ha specificità
 * zero: un `min-w-[900px]` sulla tabella lo sostituisce e la tabella torna
 * larga quanto il contenuto. La soglia sotto cui scorrere va scritta come
 * `min-w-[max(100%,900px)]`. Mai `w-full`: con la larghezza fissata scatta il
 * `table-fixed` di Flux, le colonne diventano uguali e il testo, che non va a
 * capo, sborda nella cella accanto.
 */
class FluxTableFullWidthTest extends TestCase
{
    public function test_no_flux_table_shrinks_below_the_width_of_its_container(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all('/<flux:table(?![.\w-])([^>]*)>/s', $file->getContents(), $tags);

            foreach ($tags[1] as $attributes) {
                preg_match('/\sclass="([^"]*)"/', $attributes, $class);
                $classes = preg_split('/\s+/', $class[1] ?? '', -1, PREG_SPLIT_NO_EMPTY);

                foreach ($classes as $name) {
                    $fixedMinimum = str_starts_with($name, 'min-w-[') && ! str_starts_with($name, 'min-w-[max(100%,');

                    if ($fixedMinimum || in_array($name, ['w-full', 'w-auto', 'w-fit', 'w-max'], true) || str_starts_with($name, 'max-w-')) {
                        $offenders[] = $file->getRelativePathname().': '.$name;
                    }
                }
            }
        }

        $this->assertSame([], $offenders);
    }
}
