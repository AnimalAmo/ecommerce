<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    public static function gatewayCodeProvider(): array
    {
        return [
            'card via stripe' => [PaymentMethod::Card, 'stripe'],
            'apple pay via stripe' => [PaymentMethod::ApplePay, 'stripe'],
            'google pay via stripe' => [PaymentMethod::GooglePay, 'stripe'],
            'klarna via stripe' => [PaymentMethod::Klarna, 'stripe'],
            'paypal via paypal' => [PaymentMethod::Paypal, 'paypal'],
        ];
    }

    #[DataProvider('gatewayCodeProvider')]
    public function test_gateway_code(PaymentMethod $method, string $expected): void
    {
        $this->assertSame($expected, $method->gatewayCode());
    }

    public static function stripeTypesProvider(): array
    {
        return [
            'card' => [PaymentMethod::Card, ['card']],
            'apple pay' => [PaymentMethod::ApplePay, ['card']],
            'google pay' => [PaymentMethod::GooglePay, ['card']],
            'klarna' => [PaymentMethod::Klarna, ['klarna']],
            'paypal (non stripe)' => [PaymentMethod::Paypal, null],
        ];
    }

    #[DataProvider('stripeTypesProvider')]
    public function test_stripe_payment_method_types(PaymentMethod $method, ?array $expected): void
    {
        $this->assertSame($expected, $method->stripePaymentMethodTypes());
    }

    public function test_labels_come_from_lang_payment(): void
    {
        $this->assertSame('Carta di credito o di debito', PaymentMethod::Card->label());
        $this->assertSame('Apple Pay', PaymentMethod::ApplePay->label());
        $this->assertSame('Google Pay', PaymentMethod::GooglePay->label());
        $this->assertSame('Klarna', PaymentMethod::Klarna->label());
        $this->assertSame('PayPal', PaymentMethod::Paypal->label());
    }

    public function test_only_wallets_use_express_checkout(): void
    {
        $this->assertTrue(PaymentMethod::ApplePay->usesExpressCheckout());
        $this->assertTrue(PaymentMethod::GooglePay->usesExpressCheckout());
        $this->assertFalse(PaymentMethod::Card->usesExpressCheckout());
        $this->assertFalse(PaymentMethod::Klarna->usesExpressCheckout());
        $this->assertFalse(PaymentMethod::Paypal->usesExpressCheckout());
    }
}
