<?php

use App\Services\Content\ContentBlockService;

if (! function_exists('cms')) {
    /**
     * Testo di una pagina pubblica modificabile dal pannello: il testo scritto
     * dalla cliente per la lingua corrente, se c'è, altrimenti quello del file
     * lingua. Stessa firma di __(), stessi segnaposto (:name).
     *
     * @param  array<string, mixed>  $replace
     */
    function cms(string $key, array $replace = []): string
    {
        return app(ContentBlockService::class)->text($key, $replace);
    }
}

if (! function_exists('cms_paragraphs')) {
    /**
     * Come cms() per le chiavi che nel file lingua sono un elenco di paragrafi
     * (about.body): il testo della cliente si divide sulle righe vuote.
     *
     * @return list<string>
     */
    function cms_paragraphs(string $key): array
    {
        return app(ContentBlockService::class)->paragraphs($key);
    }
}
