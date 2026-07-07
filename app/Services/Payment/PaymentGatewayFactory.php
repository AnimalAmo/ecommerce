<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Risolve il gateway del metodo di pagamento (PaymentMethod->gatewayCode()).
 * La risoluzione può lanciare PaymentConfigurationException (chiavi mancanti):
 * va catturata a monte (toast al checkout, 400 nei webhook), mai qui.
 */
class PaymentGatewayFactory
{
    public function __construct(private readonly Container $container) {}

    public function make(PaymentMethod $method): PaymentGatewayInterface
    {
        $gateway = match ($method->gatewayCode()) {
            'stripe' => StripeGateway::class,
            'paypal' => PaypalGateway::class,
            default => throw new InvalidArgumentException(
                "Unsupported gateway code [{$method->gatewayCode()}].",
            ),
        };

        return $this->container->make($gateway);
    }
}
