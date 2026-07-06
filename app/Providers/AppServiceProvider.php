<?php

namespace App\Providers;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

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
        // Alias morph stabili per i target polimorfici (amenities/faqs/reviews, favorites)
        // e per model_has_roles di spatie/laravel-permission ('user').
        Relation::enforceMorphMap([
            'structure' => Structure::class,
            'event' => Event::class,
            'smartbox_package' => SmartboxPackage::class,
            'user' => User::class,
        ]);
    }
}
