{{--
    Sitemap XML (SitemapController + SitemapService).

    Una url per lingua, ognuna con TUTTI gli alternate, la propria compresa: è
    la forma che Google documenta, e un alternate scritto solo sulla versione
    italiana lascerebbe l'inglese a farsi concorrenza da solo.

    I commenti stanno tutti quassù, fuori dai cicli: un commento Blade dentro il
    ciclo lascerebbe nel documento una riga vuota per ogni URL. Attenzione, i
    commenti Blade non si annidano e non possono contenere altra sintassi Blade:
    la prima chiusura incontrata chiude tutto e il resto finisce nel documento.

    La dichiarazione XML è stampata come stringa perché scritta com'è verrebbe
    letta come apertura di tag PHP se short_open_tag fosse attivo; l'a capo che
    questo commento lascia in testa lo toglie il trim() del controller (uno
    spazio prima della dichiarazione rende il documento malformato).
--}}
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach ($entry['urls'] as $url)
    <url>
        <loc>{{ $url }}</loc>
@if ($entry['lastmod'] !== null)
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
@foreach ($entry['urls'] as $locale => $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternate }}" />
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['urls'][$defaultLocale] }}" />
    </url>
@endforeach
@endforeach
</urlset>
