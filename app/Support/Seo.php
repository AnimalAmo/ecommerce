<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Tutto ciò che il <head> del layout deve sapere su una pagina: description,
 * canonical, alternate hreflang, immagine di condivisione.
 *
 * Sta qui e non nei componenti Livewire perché il layout è uno solo e le
 * pagine sono decine: la description si indicizza sul nome della rotta
 * (lang/{it,en}/seo.php), gli URL si derivano SEMPRE dalla request corrente
 * e dagli helper di rotta. Nessun host cablato: il dominio di produzione non
 * è ancora attivo e lo stesso codice gira oggi su animalamo.test e in staging.
 */
final class Seo
{
    /**
     * Immagine della card social. È l'hero della home: l'unico asset di brand
     * in public/img che superi i 1200x630 richiesti da summary_large_image
     * (il logo è un SVG, che i social non scaricano). Quando il cliente
     * consegnerà una card 1200x630 dedicata basterà cambiare queste tre
     * costanti — MetaTagsTest verifica che il file esista e sia grande abbastanza.
     */
    public const SHARE_IMAGE = 'img/home-hero.jpg';

    public const SHARE_IMAGE_WIDTH = 1920;

    public const SHARE_IMAGE_HEIGHT = 976;

    /**
     * Description della rotta corrente, con ripiego sulla description di sito.
     *
     * Le chiavi di seo.php sono i nomi di rotta ('holiday.region'): __() le
     * risolve anche col punto dentro, perché Arr::get() prova prima la chiave
     * esatta e solo dopo la scompone in segmenti.
     */
    public static function description(): string
    {
        $route = request()->route()?->getName();

        if ($route !== null && Lang::has("seo.{$route}")) {
            return (string) __("seo.{$route}");
        }

        return (string) __('seo.default');
    }

    /**
     * URL canonica: quella corrente senza query string.
     *
     * I filtri del catalogo e i parametri di campagna (?utm_source=…) moltiplicano
     * le URL della stessa pagina; senza canonical pulita Google le tratta come
     * pagine distinte e ne indicizza una a caso.
     */
    public static function canonical(): string
    {
        return self::normalize(URL::current());
    }

    /**
     * Forma unica di una URL: niente query string, niente slash finale.
     *
     * Canonical e hreflang devono uscire IDENTICHE carattere per carattere,
     * altrimenti dichiarano due URL diverse per la stessa pagina. Il caso in
     * cui divergono è la home: URL::current() e route('home') danno
     * "https://dominio", mentre mcamara ci rimette lo slash finale. Vince la
     * forma che usa già tutto il resto del sito (i link li fa route()).
     */
    private static function normalize(string $url): string
    {
        return rtrim(Str::before($url, '?'), '/');
    }

    /**
     * Alternate hreflang: locale => URL della stessa pagina nell'altra lingua.
     *
     * Le costruisce mcamara, non una concatenazione di "/en": gli slug sono
     * tradotti (chi-siamo ↔ about-us) e le rotte hanno parametri, quindi solo
     * getLocalizedURL() sa risalire dalla rotta corrente alla sua gemella.
     * Passandogli null come URL usa la request corrente e i parametri della
     * rotta corrente; la query string però se la ritrova in coda, e in un
     * hreflang non ci va (vale lo stesso motivo della canonical).
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $alternates = [];

        foreach (array_keys(LaravelLocalization::getSupportedLocales()) as $locale) {
            $url = LaravelLocalization::getLocalizedURL($locale, null, [], false);

            // false = quella lingua non ha una traduzione per questa rotta:
            // meglio nessun alternate che un alternate rotto.
            if (! is_string($url) || $url === '') {
                continue;
            }

            $alternates[$locale] = self::normalize($url);
        }

        return $alternates;
    }

    /**
     * La lingua di x-default, cioè la versione servita a chi non parla nessuna
     * delle lingue dichiarate: per un sito italiano è l'italiano, ed è la
     * stessa che vive senza prefisso nelle URL.
     */
    public static function defaultLocale(): string
    {
        return LaravelLocalization::getDefaultLocale();
    }

    /**
     * Il codice regionale che Open Graph si aspetta (it_IT, en_GB): non il
     * codice di lingua, che Facebook scarta silenziosamente.
     */
    public static function regional(string $locale): string
    {
        return (string) config("laravellocalization.supportedLocales.{$locale}.regional", $locale);
    }

    /**
     * I regionali delle ALTRE lingue, per og:locale:alternate.
     *
     * @return array<int, string>
     */
    public static function alternateRegionals(): array
    {
        $current = app()->getLocale();

        return collect(array_keys(LaravelLocalization::getSupportedLocales()))
            ->reject(fn (string $locale) => $locale === $current)
            ->map(fn (string $locale) => self::regional($locale))
            ->values()
            ->all();
    }

    /** URL assoluta dell'immagine di condivisione: i social la scaricano da sé. */
    public static function image(): string
    {
        return asset(self::SHARE_IMAGE);
    }

    /**
     * Se la pagina va offerta ai motori di ricerca.
     *
     * Stesso criterio di App\Http\Middleware\NoIndexPartnerPages, che manda
     * X-Robots-Tag: noindex su tutte le rotte partner.*: su quelle pagine
     * canonical, hreflang e Open Graph direbbero l'esatto contrario dell'header
     * e le proporrebbero comunque all'indicizzazione.
     */
    public static function isIndexable(): bool
    {
        return ! str_starts_with((string) request()->route()?->getName(), 'partner.');
    }
}
