<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class LineDiscount
{
    public function __construct(
        public string $id,
        public string $discountItemId,
        public string $name,
        public int $amount,
    ) {

    }
}