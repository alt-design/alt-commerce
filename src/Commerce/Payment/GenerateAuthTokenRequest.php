<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Customer\Address;

class GenerateAuthTokenRequest
{
    /**
     * Customer/shipping details are optional; gateways that support native
     * fields (e.g. Stripe receipt_email + shipping) map them when present.
     */
    public function __construct(
        public string|null $customerId = null,
        public string|null $description = null,
        public string|null $customerName = null,
        public string|null $customerEmail = null,
        public string|null $customerPhone = null,
        public Address|null $shippingAddress = null,
    ) {

    }
}
