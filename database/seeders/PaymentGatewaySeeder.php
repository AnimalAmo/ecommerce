<?php

namespace Database\Seeders;

use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /** Gateway del checkout: solo stripe (card + wallet ECE). Idempotente; rimuove l'eventuale riga paypal legacy. */
    public function run(): void
    {
        PaymentGateway::updateOrCreate(
            ['code' => 'stripe'],
            ['code' => 'stripe', 'name' => 'Stripe', 'is_enabled' => true, 'sort_order' => 0],
        );

        PaymentGateway::query()->where('code', 'paypal')->delete();
    }
}
