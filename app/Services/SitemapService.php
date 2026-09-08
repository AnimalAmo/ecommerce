<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Article\Article;
use App\Models\Event\Event;
use App\Models\Page\Page;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use Carbon\CarbonInterface;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Costruisce l'elenco di URL della sitemap: una voce per pagina, con l'URL
 * tradotto in ognuna delle lingue supportate (config/laravellocalization.php).
 *
 * Nessun host scritto a mano: gli URL nascono da LaravelLocalization, cioè
 * dagli slug di lang/{it,en}/routes.php sulla radice della richiesta. Il
 * dominio di produzione non è ancora attivo e lo stesso codice gira oggi su
 * staging: un host in costante manderebbe i crawler sul sito sbagliato.
 *
 * Il catalogo può essere VUOTO (giorno 1 su animalamo.it): le query sulle
 * tabelle vuote tornano zero righe e la sitemap resta un documento valido con
 * le sole pagine statiche. È il caso normale, non un errore da gestire.
 */
class SitemapService
{
    /**
     * Pagine pubbliche senza parametri, nell'ordine in cui compaiono nel file.
     * Sono nomi di rotta: lo slug tradotto lo risolve LaravelLocalization.
     *
     * Fuori da qui restano le pagine di sessione (carrello, checkout, profilo,
     * preferiti) e il reset password: contenuto diverso a ogni visita — o un
     * token nell'URL — quindi niente da indicizzare. "Lavora con noi" invece
     * c'è: è la vetrina del funnel partner, l'unica pagina B2B che
     * NoIndexPartnerPages lascia deliberatamente indicizzabile.
     *
     * @var list<string>
     */
    private const STATIC_ROUTES = [
        'home',
        'holiday',
        'eventi',
        'smartbox',
        'news',
        'community',
        'about',
        'contact',
        'work-with-us',
    ];

    /**
     * Pagine legali: nome della rotta => slug della riga Page che le dà il
     * testo. La data di revisione (last_updated_at) sta lì, non nella rotta.
     *
     * @var array<string, string>
     */
    private const LEGAL_ROUTES = [
        'terms.customers' => Page::TERMS_CUSTOMERS,
        'terms.suppliers' => Page::TERMS_SUPPLIERS,
        'privacy' => Page::PRIVACY,
    ];

    /**
     * Le voci della sitemap: ogni voce è la stessa pagina in tutte le lingue
     * (`urls`: locale => URL assoluto) più, dove esiste, la data di revisione.
     *
     * @return list<array{urls: array<string, string>, lastmod: string|null}>
     */
    public function entries(): array
    {
        return array_values(array_filter([
            ...$this->staticPages(),
            ...$this->legalPages(),
            ...$this->regions(),
            ...$this->events(),
            ...$this->smartboxPackages(),
            ...$this->articles(),
        ]));
    }

    /**
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function staticPages(): array
    {
        return array_map(fn (string $route) => $this->entry($route), self::STATIC_ROUTES);
    }

    /**
     * Le pagine legali restano in elenco anche senza la loro riga a DB: sono
     * rotte del sito, non contenuto opzionale. Senza riga manca solo il
     * lastmod. Una query sola per tutte e tre.
     *
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function legalPages(): array
    {
        $revisions = Page::query()->pluck('last_updated_at', 'slug');

        $entries = [];

        foreach (self::LEGAL_ROUTES as $route => $slug) {
            $entries[] = $this->entry($route, [], $revisions[$slug] ?? null);
        }

        return $entries;
    }

    /**
     * Tutte le regioni, anche quelle ancora senza strutture: sono dati di
     * piattaforma (non mock) e la pagina esiste comunque — /animal-holiday le
     * elenca tutte per la stessa ragione.
     *
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function regions(): array
    {
        return Region::query()
            ->orderBy('position')
            ->pluck('slug')
            ->map(fn (string $slug) => $this->entry('holiday.region', ['region' => $slug]))
            ->all();
    }

    /**
     * Attività ed eventi hanno due schede distinte (/eventi/attivita/{slug} e
     * /eventi/{slug}): sbagliare rotta manderebbe i crawler su un redirect.
     *
     * Nella tabella events ci sono solo righe già pubblicate — le bozze dei
     * partner vivono in structure_drafts finché il wizard non le pubblica —
     * quindi non serve nessun filtro di stato.
     *
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function events(): array
    {
        return Event::query()
            ->whereIn('type', [ProductType::Activity, ProductType::Event])
            ->orderBy('id')
            ->get(['id', 'slug', 'type'])
            ->map(fn (Event $event) => $event->type === ProductType::Activity
                ? $this->entry('eventi.activity', ['activity' => $event->slug])
                : $this->entry('eventi.detail', ['event' => $event->slug]))
            ->all();
    }

    /**
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function smartboxPackages(): array
    {
        return SmartboxPackage::query()
            ->orderBy('id')
            ->pluck('slug')
            ->map(fn (string $slug) => $this->entry('smartbox.detail', ['box' => $slug]))
            ->all();
    }

    /**
     * Solo gli articoli pubblicati: su uno con data futura /news/{slug}
     * risponde 404 (NewsDetail usa lo stesso scope), e dichiarare a Google un
     * URL che sappiamo essere un 404 è peggio che tacerlo.
     *
     * Il lastmod è published_at, la data dichiarata dalla redazione, non
     * updated_at: quest'ultima cambia a ogni riesecuzione dell'ArticleSeeder e
     * farebbe sembrare aggiornato un testo immutato (stessa scelta della
     * migration articles).
     *
     * @return list<array{urls: array<string, string>, lastmod: string|null}|null>
     */
    private function articles(): array
    {
        return Article::published()
            ->get(['id', 'slug', 'published_at'])
            ->map(fn (Article $article) => $this->entry(
                'news.detail',
                ['article' => $article->slug],
                $article->published_at,
            ))
            ->all();
    }

    /**
     * Una voce: lo stesso contenuto nelle due lingue. La data arriva solo dove
     * ne esiste una vera (articoli e pagine legali); altrove niente lastmod,
     * perché un now() o un updated_at inventato insegna ai motori che il
     * nostro lastmod non vale niente.
     *
     * @param  array<string, string>  $parameters
     * @return array{urls: array<string, string>, lastmod: string|null}|null
     */
    private function entry(string $routeName, array $parameters = [], ?CarbonInterface $lastmod = null): ?array
    {
        if (! $this->isIndexable($routeName)) {
            return null;
        }

        $urls = [];

        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $locale) {
            $urls[$locale] = $this->localizedUrl($locale, $routeName, $parameters);
        }

        return ['urls' => $urls, 'lastmod' => $lastmod?->format('Y-m-d')];
    }

    /**
     * Stessa regola di NoIndexPartnerPages: ogni rotta partner.* esce con
     * X-Robots-Tag: noindex. Elencarla qui direbbe a Google il contrario di
     * quello che dice l'header — e la contraddizione nascerebbe in silenzio il
     * giorno in cui qualcuno aggiunge una rotta partner agli elenchi sopra.
     */
    private function isIndexable(string $routeName): bool
    {
        return ! str_starts_with($routeName, 'partner.');
    }

    /**
     * URL assoluto della rotta in una lingua. Gli slug stanno in
     * lang/{locale}/routes.php e il prefisso lo mette mcamara (l'italiano, di
     * default, non ne ha).
     *
     * @param  array<string, string>  $parameters
     */
    private function localizedUrl(string $locale, string $routeName, array $parameters): string
    {
        // La home non ha uno slug da tradurre: in italiano
        // getURLFromRouteNameTranslated() tornerebbe false (nessuna chiave
        // 'routes.home' e prefisso nascosto), quindi il suo URL è la radice —
        // o il solo prefisso di lingua nelle altre lingue.
        if ($routeName === 'home') {
            return $locale === LaravelLocalization::getDefaultLocale()
                ? url('/')
                : url($locale);
        }

        return (string) LaravelLocalization::getURLFromRouteNameTranslated($locale, 'routes.'.$routeName, $parameters);
    }
}
