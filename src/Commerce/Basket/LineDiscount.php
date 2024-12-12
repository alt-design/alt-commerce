<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Enum\DiscountType;

class LineDiscount
{
    public function __construct(
        public string $id,
        public DiscountType $discountType,
        public int $discountAmount,
    ) {

    }
}