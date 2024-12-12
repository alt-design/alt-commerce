<?php

namespace AltDesign\AltCommerce\Commerce\Coupon;

use AltDesign\AltCommerce\Enum\DiscountType;

class PercentageDiscountCoupon extends BaseCoupon
{
    public function discountType(): DiscountType
    {
        return DiscountType::PERCENTAGE;
    }
}