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

        $name = (string) $request->route()?->getName();

        // Area partner e pannello di amministrazione: niente indicizzazione.
        if (str_starts_with($name, 'partner.') || str_starts_with($name, 'admin.')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
