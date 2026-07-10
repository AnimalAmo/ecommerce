<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protegge l'area riservata partner: l'utente autenticato deve avere il ruolo
 * `partner` ED essere attivo (`is_active`). Gli ospiti sono già gestiti da
 * `auth` (redirect alla home); qui trattiamo l'autenticato non autorizzato con
 * un 403, senza rivelare l'esistenza delle risorse.
 */
class EnsureActivePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->hasRole('partner'), 403);

        return $next($request);
    }
}
