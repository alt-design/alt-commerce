<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\Coupon;

class CouponItem
{

    public function __construct(
        public string $id,
        public Coupon $coupon,
    )
    {

    }


}