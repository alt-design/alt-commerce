<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\Resolver;

class PaymentManager
{
    public function __construct(
        protected Resolver $resolver,
        protected GatewayBroker $gatewayBroker,
    )
    {

    }

    public function currency(string $currency): PaymentContext
    {
        return $this->context($currency);
    }

    protected function context(string $currency): PaymentContext
    {
        return new PaymentContext(
            resolver: $this->resolver,
            gatewayBroker: $this->gatewayBroker,
            currency: $currency
        );
    }
}