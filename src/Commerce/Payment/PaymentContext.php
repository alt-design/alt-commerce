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

    /**
     * Accepts either a customer id (original signature) or a full
     * GenerateAuthTokenRequest carrying customer/shipping details for
     * gateways with native fields.
     */
    public function authToken(GenerateAuthTokenRequest|string|null $request = null)
    {
        if (! $request instanceof GenerateAuthTokenRequest) {
            $request = new GenerateAuthTokenRequest(customerId: $request);
        }

        return $this->gatewayBroker->currency($this->currency)->gateway()->createPaymentNonceAuthToken($request);
    }
}