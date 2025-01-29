<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

class PaymentManager
{
    public function __construct(protected GatewayBroker $gatewayBroker)
    {

    }

    public function authToken(string $currency): string
    {
        return $this->gatewayBroker->currency($currency)->gateway()->createPaymentNonceAuthToken();
    }
}