<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class DeliveryItem
{
    public function __construct(
        public string $name,
        public int $amount,
    ) {

    }

}