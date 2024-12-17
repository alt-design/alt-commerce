<?php

namespace AltDesign\AltCommerce\Commerce\Customer;

final class Address
{
    public function __construct(
        public string|null $company = null,
        public string|null $fullName = null,
        public string|null $countryCode = null,
        public string|null $postalCode = null,
        public string|null $region = null,
        public string|null $locality = null,
        public string|null $street = null,
        public string|null $phoneNumber = null,
    ) {

    }
}