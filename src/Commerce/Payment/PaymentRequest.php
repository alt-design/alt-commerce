<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Customer\Address;

class PaymentRequest
{
    /**
     * @param string $token
     * @param string $currency
     * @param string $orderNumber
     * @param Address|null $billingAddress
     * @param int $total
     * @param array<string, string> $additional
     */
    public function __construct(
        public string $token,
        public string $currency,
        public string $orderNumber,
        public Address|null $billingAddress,
        public int $total,
        public array $additional = [],
    )
    {

    }
}