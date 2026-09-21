<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pannello di amministrazione: solo account attivi con ruolo superadmin.
 *
 * L'ospite è già stato respinto da `auth` (che lo manda al login del
 * pannello); qui arriva un utente autenticato. Cliente o partner ricevono un
 * 403 e non un redirect: non devono scoprire dove sta il form d'accesso.
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->is_active && $user->anonymized_at === null && $user->hasRole('superadmin'),
            403,
        );

        return $next($request);
    }
}
