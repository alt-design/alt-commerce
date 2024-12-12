<?php

namespace AltDesign\AltCommerce\Commerce\PaymentGateway;

use AltDesign\AltCommerce\Commerce\Customer\Address;

class PaymentRequest
{
    public function __construct(
        public string $token,
        public string $currency,
        public string $orderNumber,
        public Address $billingAddress,
        public int $total,
    )
    {

    }
}