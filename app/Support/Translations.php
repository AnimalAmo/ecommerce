<?php

namespace App\Support;

/**
 * Valori di un campo tradotto (spatie/laravel-translatable) pronti per
 * `fill()` / `create()` / `updateOrCreate()`: il modo unico con cui i testi
 * liberi localizzati del partner arrivano a database.
 *
 * Difetto W5 dell'audit dei flussi (28/09/2026): gli step scrivevano
 * `array_filter($campo, filled)`, che fa CADERE la chiave della lingua
 * svuotata; `setTranslations()` di spatie itera solo le chiavi che riceve e
 * non rimuove le altre. Una traduzione inglese salvata una volta non si
 * toglieva più: svuotato il tab EN, la bozza teneva «Dog Fair» e il
 * visitatore su /en non tornava mai al fallback italiano. L'unica uscita,
 * svuotare TUTTE le lingue, era chiusa dal `required` sull'italiano.
 *
 * Il lato contenuti toglie una lingua con `forgetTranslation()` /
 * `replaceTranslations()` (ArticleService, FaqService, PageService,
 * ContentBlockService), ma lì il servizio tiene in mano il modello che poi
 * salva. Qui i valori viaggiano come ARRAY verso un `fill()` che non vede
 * questo codice: `saveStep()` del trait del wizard, `toDraft()` fuso dal
 * pannello admin nella sua `create()`, `toProfile()` passato a
 * `updateOrCreate()`. Dentro `fill()` il solo modo di togliere una lingua è
 * scriverla a null: `getTranslations()` scarta i null
 * (`allowNullForTranslation` è false, e AppServiceProvider imposta solo il
 * fallback), quindi quella lingua risulta mancante in ogni lettura —
 * `getTranslation()`, `hasTranslation()`, `getTranslatedLocales()`,
 * `toArray()` — e su EN scatta il fallback IT, lo stesso esito di
 * `forgetTranslation()`.
 *
 * Si coprono TUTTE le lingue del sito, non solo le chiavi ricevute: chi passa
 * `$model->getTranslations()` (che non riporta le lingue già vuote) ottiene
 * comunque la chiave a null che sovrascrive la copia vecchia, ed è ciò che
 * serve a un publisher che ricopia la bozza su una riga di catalogo già
 * esistente.
 */
final class Translations
{
    /**
     * @param  array<string, mixed>  $values  lingua => testo, nella forma delle property degli step (`['it' => '', 'en' => '']`)
     * @return array<string, mixed> ogni lingua del sito più quelle ricevute; le vuote (blank) a null
     */
    public static function replacing(array $values): array
    {
        $locales = array_unique([
            ...array_keys(config('laravellocalization.supportedLocales', [])),
            ...array_keys($values),
        ]);

        $replacing = [];

        foreach ($locales as $locale) {
            $value = $values[$locale] ?? null;
            $replacing[$locale] = filled($value) ? $value : null;
        }

        return $replacing;
    }
}
