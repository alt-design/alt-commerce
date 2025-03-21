<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\Customer;

class GenerateAuthTokenRequest
{
    public function __construct(
        public Customer|null $customer = null
    ) {

    }
}