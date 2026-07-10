<?php

namespace App\Providers;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In produzione tutti gli URL generati (link, asset, webhook nelle email di
        // conferma, callback dei gateway) devono essere HTTPS anche dietro un proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Alias morph stabili per i target polimorfici (amenities/faqs/reviews, favorites)
        // e per model_has_roles di spatie/laravel-permission ('user').
        Relation::enforceMorphMap([
            'structure' => Structure::class,
            'event' => Event::class,
            'smartbox_package' => SmartboxPackage::class,
            'user' => User::class,
        ]);

        // Contenuti partner (spatie/laravel-translatable): l'italiano è la lingua
        // richiesta, l'inglese opzionale — su locale EN senza traduzione si mostra
        // l'IT. (Diverso da app.fallback_locale=en, che riguarda i lang file UI.)
        Translatable::fallback(fallbackLocale: 'it');
    }
}
