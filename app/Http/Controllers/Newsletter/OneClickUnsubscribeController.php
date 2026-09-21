<?php

namespace App\Http\Controllers\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterUrls;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Disiscrizione a un clic (RFC 8058), l'URL dell'header List-Unsubscribe.
 * La chiama in POST il provider di posta (Gmail, Yahoo…) quando l'utente
 * preme "Annulla iscrizione" accanto al mittente: nessuna sessione, nessun
 * token CSRF, nessuna pagina. La firma dell'URL è l'autenticazione.
 *
 * Un GET non disiscrive (i filtri antispam aprono i link da soli): i client
 * che aprono l'header nel browser arrivano alla pagina con il pulsante.
 */
class OneClickUnsubscribeController
{
    public function __invoke(
        Request $request,
        NewsletterSubscriber $subscriber,
        SubscriptionService $subscriptions,
        NewsletterUrls $urls,
    ): Response|RedirectResponse {
        if ($request->isMethod('get')) {
            return redirect()->away($urls->unsubscribePage($subscriber));
        }

        $subscriptions->unsubscribe($subscriber);

        return response('', 200);
    }
}
