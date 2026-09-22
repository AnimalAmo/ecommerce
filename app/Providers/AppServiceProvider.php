<?php

namespace App\Providers;

use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\UseItalianLocale;
use App\Models\Article\Article;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\AdminCounters;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Layout e home del pannello chiedono gli stessi contatori: una volta per request.
        $this->app->scoped(AdminCounters::class);

        // Schede, carrello e checkout chiedono la modalità dello stesso partner
        // più volte: una lettura per request, e nessuna memoria fra una request e l'altra.
        $this->app->scoped(PartnerPaymentModeService::class);
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
            // Proprietari di media (spatie/laravel-medialibrary): copertine di Animal Times.
            'article' => Article::class,
        ]);

        // Contenuti partner (spatie/laravel-translatable): l'italiano è la lingua
        // richiesta, l'inglese opzionale — su locale EN senza traduzione si mostra
        // l'IT. (Diverso da app.fallback_locale=en, che riguarda i lang file UI.)
        Translatable::fallback(fallbackLocale: 'it');

        // Le azioni Livewire (POST /livewire/update) non ripassano dal gruppo di
        // rotte: senza questa riga un wire:click del pannello girerebbe senza il
        // controllo superadmin e con la lingua di configurazione. Livewire le
        // riapplica solo se la rotta d'origine le aveva.
        Livewire::addPersistentMiddleware([EnsureSuperadmin::class, UseItalianLocale::class]);
    }
}
