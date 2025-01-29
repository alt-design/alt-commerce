<?php

namespace AltDesign\AltCommerce\Commerce\Payment;


use AltDesign\AltCommerce\Commerce\Customer\Address;

class CreatePaymentRequest
{
    public function __construct(
        public string $gatewayPaymentMethodToken,
        public string $gatewayCustomerId,
        public int $amount,
        public string|null $descriptor = null,
        public Address|null $billingAddress = null
    )
    {
    }
}