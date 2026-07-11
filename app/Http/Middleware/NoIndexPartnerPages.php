<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le pagine dell'area partner (iscrizione, wizard, area riservata) non vanno
 * indicizzate dai motori di ricerca: X-Robots-Tag su tutte le route partner.*.
 * "Lavora con noi" resta indicizzabile: è la pagina vetrina del sito pubblico.
 */
class NoIndexPartnerPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_starts_with((string) $request->route()?->getName(), 'partner.')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
