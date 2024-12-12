<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\DiscountItem;

class CouponDiscountItem implements DiscountItem
{
    public function __construct(
        protected string $name,
        protected int $amount,
        protected Coupon $coupon
    )
    {

    }

    public function coupon(): Coupon
    {
        return $this->coupon;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function amount(): int
    {
        return $this->amount;
    }
}