<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class TaxItem
{
    public function __construct(
        public string $name,
        public int $amount,
        public float $rate
    )
    {

    }

}