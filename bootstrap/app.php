<?php

use App\Http\Middleware\EnsureActivePartner;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\NoIndexPartnerPages;
use App\Http\Middleware\UseItalianLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Pannello di amministrazione: FUORI dal gruppo localizzato. È solo in
        // italiano, e un prefisso di lingua non servirebbe a nessuno.
        then: function (): void {
            Route::middleware(['web', UseItalianLocale::class])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Niente pagina /login sul sito: gli ospiti sulle rotte protette tornano
        // alla home. Il pannello invece ha il suo accesso.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('home'));

        // I webhook dei gateway arrivano senza sessione: esenti da CSRF
        // (la firma dell'evento è la loro autenticazione).
        $middleware->preventRequestForgery(except: [
            'webhooks/*',
        ]);

        // L'area partner (route partner.*) non va indicizzata dai motori.
        $middleware->appendToGroup('web', NoIndexPartnerPages::class);

        $middleware->alias([
            'partner' => EnsureActivePartner::class,
            'superadmin' => EnsureSuperadmin::class,
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
