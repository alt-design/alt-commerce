<?php

namespace AltDesign\AltCommerce\PaymentGateways\Mock;

use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class MockGatewayDriver implements PaymentGatewayDriver
{

    public function name(): string
    {
        return 'mock';
    }

    public function factory(Resolver $resolver): PaymentGatewayFactory
    {
        return new MockGatewayFactory($resolver);
    }
}