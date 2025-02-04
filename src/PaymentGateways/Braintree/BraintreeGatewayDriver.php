<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;
use AltDesign\AltCommerce\Contracts\Resolver;

class BraintreeGatewayDriver implements PaymentGatewayDriver
{

    public function name(): string
    {
        return 'braintree';
    }

    public function factory(Resolver $resolver): BraintreeGatewayFactory
    {
        return new BraintreeGatewayFactory($resolver);
    }
}