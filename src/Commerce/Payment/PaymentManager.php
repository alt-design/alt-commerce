<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\BasketRepository;

class PaymentManager
{
    public function __construct(
        protected GatewayBroker $gatewayBroker,
        protected BasketRepository $basketRepository,
    )
    {

    }

    public function authToken(): string
    {
        $basket = $this->basketRepository->get();
        return $this->gatewayBroker->currency($basket->currency)->gateway()->createPaymentNonceAuthToken();
    }
}