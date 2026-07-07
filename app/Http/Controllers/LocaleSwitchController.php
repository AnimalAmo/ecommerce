<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LocaleSwitchController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        // Locale non tra i supportati: nessun cambio, torna alla home (mai redirect arbitrario).
        if (! array_key_exists($locale, LaravelLocalization::getSupportedLocales())) {
            return redirect(LaravelLocalization::getLocalizedURL(app()->getLocale(), url('/')));
        }

        session()->put('locale', $locale);
        LaravelLocalization::setLocale($locale);

        // Solo un "indietro" same-origin è ammesso: il Referer è controllabile dal client,
        // quindi un host esterno viene scartato in favore della home (no open redirect).
        $previous = url()->previous();
        $host = parse_url($previous, PHP_URL_HOST);

        if ($host !== null && $host !== request()->getHost()) {
            $previous = url('/');
        }

        return redirect(LaravelLocalization::getLocalizedURL($locale, $previous));
    }
}
