<?php

namespace AltDesign\AltCommerce\Commerce\Customer;

class Customer
{
    public function __construct(
        public string $name,
        public string $email,
        public Address $shippingAddress,
        public Address $billingAddress,
    ) {

    }
}