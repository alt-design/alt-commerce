<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Enum\DiscountType;

class DiscountItem
{
    public function __construct(
        public string $id,
        public string $name,
        public int $amount,
        public DiscountType $type,
        public string|null $couponCode = null,
    )
    {

    }
}