<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class FeeItem
{
    public function __construct(
        public string $name,
        public int $amount
    )
    {

    }
}