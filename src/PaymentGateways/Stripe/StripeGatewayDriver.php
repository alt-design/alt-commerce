<?php

namespace AltDesign\AltCommerce\PaymentGateways\Stripe;

use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;
use AltDesign\AltCommerce\Contracts\Resolver;

class StripeGatewayDriver implements PaymentGatewayDriver
{

    public function name(): string
    {
        return 'stripe';
    }

    public function factory(Resolver $resolver): StripeGatewayFactory
    {
        return new StripeGatewayFactory($resolver);
    }
}