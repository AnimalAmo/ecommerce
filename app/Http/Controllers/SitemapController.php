<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * /sitemap.xml, generata a ogni richiesta. Nessuna cache per ora: il documento
 * è di poche decine di URL e cinque query, mentre una cache stantia
 * nasconderebbe ai motori le schede appena pubblicate da un partner.
 */
class SitemapController extends Controller
{
    public function __invoke(SitemapService $sitemap): Response
    {
        // trim(): uno spazio o un a capo PRIMA della dichiarazione XML rende il
        // documento malformato e il crawler lo scarta in blocco, senza che
        // niente lo segnali. Blade un a capo in testa lo lascia volentieri.
        $xml = trim(view('sitemap', [
            'entries' => $sitemap->entries(),
            // hreflang="x-default": chi non parla né italiano né inglese
            // atterra sulla versione italiana, la lingua del sito.
            'defaultLocale' => LaravelLocalization::getDefaultLocale(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
