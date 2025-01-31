<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use AltDesign\AltCommerce\PaymentGateways\Braintree\BraintreeGateway;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class GatewayBrokerTest extends TestCase
{

    public function test_from_config(): void
    {
        $broker = new GatewayBroker([
            'enabled' => ['braintree_gbp'],
            'available' => [
                'braintree_gbp' => [
                    'driver' => 'braintree',
                    'mode' => 'sandbox',
                    'currency' => 'GBP',
                    'merchant_accounts' => [
                        'GBP' => '...merchant_account_id...'
                    ],
                    'merchant_id' => '...merchant_id...',
                    'public_key' => '...public_key...',
                    'private_key' => '...private_key...'
                ],
            ]
        ]);

        $gateway = $broker->currency('GBP')->gateway();
        $this->assertEquals(BraintreeGateway::class, get_class($gateway));
    }

    public function test_exception_is_thrown_for_missing_currency()
    {
        $this->expectException(PaymentGatewayException::class);
        $broker = new GatewayBroker([
            'enabled' => ['braintree_gbp'],
            'available' => [
                'braintree_gbp' => [
                    'driver' => 'braintree',
                    'mode' => 'sandbox',
                    'currency' => 'GBP',
                    'merchant_accounts' => [
                        'GBP' => '...merchant_account_id...'
                    ],
                    'merchant_id' => '...merchant_id...',
                    'public_key' => '...public_key...',
                    'private_key' => '...private_key...'
                ],
            ]
        ]);

        $broker->currency('USD');
    }
}