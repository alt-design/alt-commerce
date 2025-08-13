<?php

namespace AltDesign\AltCommerce\Commerce\Payment;


class GenerateAuthTokenRequest
{
    public function __construct(
        public string|null $customerId = null,
    ) {

    }
}