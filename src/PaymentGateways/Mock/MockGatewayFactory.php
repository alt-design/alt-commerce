<?php

namespace AltDesign\AltCommerce\PaymentGateways\Mock;

use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class MockGatewayFactory implements PaymentGatewayFactory
{

    public function __construct(Resolver $resolver)
    {
    }

    public function create(string $name, string $currency, array $config): PaymentGateway
    {
        return new MockGateway();
    }
}