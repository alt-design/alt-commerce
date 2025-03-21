<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;

class PaymentManager
{
    public function __construct(
        protected GatewayBroker $gatewayBroker,
        protected BasketRepository $basketRepository,
    )
    {

    }

    public function authToken(Customer|null $customer = null): string
    {
        $basket = $this->basketRepository->get();
        return $this->gatewayBroker->currency($basket->currency)->gateway()->createPaymentNonceAuthToken(
            new GenerateAuthTokenRequest(customer: $customer)
        );
    }
}