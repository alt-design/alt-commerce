<?php

namespace AltDesign\AltCommerce\Support;

class Location
{
    public function __construct(
        public string $countryCode,
        public string $currency,
    )
    {

    }
}