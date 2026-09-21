<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Il pannello sta fuori da mcamara, che è chi fissa la lingua sul sito: senza
 * questo varrebbe app.locale, e date, importi e "2 ore fa" uscirebbero nella
 * lingua di configurazione invece che in italiano.
 */
class UseItalianLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('it');

        return $next($request);
    }
}
