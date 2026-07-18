<?php

namespace App\Providers;

use App\Exceptions\PaymentConfigurationException;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // StripeClient iniettato (testabile, mai chiamate statiche): senza
        // secret la risoluzione lancia PaymentConfigurationException,
        // catturata a monte (toast config_missing / 400 webhook).
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            $secret = (string) config('payment.stripe.secret');

            if ($secret === '') {
                throw PaymentConfigurationException::missing('stripe');
            }

            return new StripeClient($secret);
        });

        $this->app->singleton(StripeGateway::class);
        $this->app->singleton(PaymentGatewayFactory::class);
        $this->app->singleton(PaymentGatewayService::class);
    }

    public function boot(): void
    {
        $this->registerWebhookRoutes();
    }

    /**
     * POST /webhooks/{code} per ogni gateway abilitato il cui controller
     * App\Http\Controllers\Webhook\{Studly}WebhookController esiste.
     * Rotte fuori dal gruppo web (niente sessione) + CSRF except in bootstrap.
     */
    private function registerWebhookRoutes(): void
    {
        foreach ($this->app->make(PaymentGatewayService::class)->enabledCodes() as $code) {
            $controller = 'App\\Http\\Controllers\\Webhook\\'.Str::studly($code).'WebhookController';

            if (! class_exists($controller)) {
                continue;
            }

            Route::middleware('throttle:60,1')
                ->post("/webhooks/{$code}", $controller)
                ->name("webhooks.{$code}");
        }
    }
}
