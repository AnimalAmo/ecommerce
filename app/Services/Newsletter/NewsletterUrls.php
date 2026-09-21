<?php

namespace App\Services\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Support\Facades\URL;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * I link che finiscono nelle mail della newsletter, nella lingua
 * dell'iscritto e non in quella della richiesta che li genera: la mail parte
 * da una coda (locale di default) o dal pannello (solo italiano), e un
 * iscritto inglese deve atterrare sulla pagina inglese.
 */
class NewsletterUrls
{
    public function confirm(NewsletterSubscriber $subscriber): string
    {
        return $this->localized($subscriber->locale, 'routes.newsletter.confirm', ['token' => $subscriber->token]);
    }

    /**
     * Pagina di disiscrizione, firmata: l'URL è la credenziale, e la firma
     * impedisce di disiscrivere gli altri cambiando l'id.
     *
     * La firma è quella di URL::signedRoute (HMAC dell'URL completo con
     * app.key), calcolata qui sull'URL localizzato perché signedRoute
     * userebbe lo slug della lingua corrente. Il middleware `signed` la
     * verifica senza sapere nulla della lingua.
     */
    public function unsubscribePage(NewsletterSubscriber $subscriber): string
    {
        $url = $this->localized($subscriber->locale, 'routes.newsletter.unsubscribe', ['subscriber' => $subscriber->getKey()]);

        return $url.'?signature='.hash_hmac('sha256', $url, (string) config('app.key'));
    }

    /**
     * Endpoint a un clic (RFC 8058) per l'header List-Unsubscribe: senza
     * lingua, perché lo chiama il provider di posta e non una persona.
     */
    public function oneClick(NewsletterSubscriber $subscriber): string
    {
        return URL::signedRoute('newsletter.one-click', ['subscriber' => $subscriber->getKey()]);
    }

    public function privacy(string $locale): string
    {
        return $this->localized($locale, 'routes.privacy');
    }

    public function home(string $locale): string
    {
        return $locale === LaravelLocalization::getDefaultLocale() ? url('/') : url($locale);
    }

    /** @param  array<string, mixed>  $parameters */
    private function localized(string $locale, string $key, array $parameters = []): string
    {
        return (string) LaravelLocalization::getURLFromRouteNameTranslated($locale, $key, $parameters);
    }
}
