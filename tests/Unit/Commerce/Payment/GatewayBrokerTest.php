<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Billing\SubscriptionFactory;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\TransactionFactory;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use AltDesign\AltCommerce\PaymentGateways\Braintree\BraintreeGateway;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class GatewayBrokerTest extends TestCase
{
    protected function setUp(): void
    {

        $this->settings = \Mockery::mock(Settings::class);
        $this->resolver = \Mockery::mock(Resolver::class);
        $this->resolver->allows('resolve')->with(TransactionFactory::class)->andReturn(new TransactionFactory());
        $this->resolver->allows('resolve')->with(SubscriptionFactory::class)->andReturn(new SubscriptionFactory());
        $this->resolver->allows('resolve')->with(Settings::class)->andReturn($this->settings);
    }

    public function test_from_config(): void
    {

        $broker = new GatewayBroker($this->resolver, [
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
        $broker = new GatewayBroker($this->resolver, [
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