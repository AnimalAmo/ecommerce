<?php

namespace Database\Seeders;

use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /** Gateway del checkout: stripe (card/wallet/klarna) e paypal (SDK classico). Idempotente. */
    public function run(): void
    {
        $gateways = [
            ['code' => 'stripe', 'name' => 'Stripe', 'is_enabled' => true, 'sort_order' => 0],
            ['code' => 'paypal', 'name' => 'PayPal', 'is_enabled' => true, 'sort_order' => 1],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway,
            );
        }
    }
}
