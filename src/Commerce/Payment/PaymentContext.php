<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\Resolver;

class PaymentContext
{
    public function __construct(
        protected Resolver $resolver,
        protected GatewayBroker $gatewayBroker,
        protected string $currency
    )
    {

    }

    public function authToken(string|null $customerId = null): string
    {
        return $this->gatewayBroker->currency($this->currency)->gateway()->createPaymentNonceAuthToken(
            new GenerateAuthTokenRequest(customerId: $customerId)
        );
    }
}