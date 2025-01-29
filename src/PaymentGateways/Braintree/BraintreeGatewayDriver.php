<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;

class BraintreeGatewayDriver implements PaymentGatewayDriver
{
    public function name(): string
    {
        return 'braintree';
    }

    public function factory(): BraintreeGatewayFactory
    {
        return new BraintreeGatewayFactory();
    }
}