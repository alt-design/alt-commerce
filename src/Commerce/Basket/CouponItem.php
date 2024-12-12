<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\Coupon;

class CouponItem
{
    public readonly string $id;

    public function __construct(
        public Coupon $coupon,
        public int $amount = 0,
    )
    {
        $this->id = 'coupon:'.$this->coupon->code();
    }


}