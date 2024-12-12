<?php

namespace AltDesign\AltCommerce\Commerce\Coupon;

use AltDesign\AltCommerce\Enum\DiscountType;

class FixedDiscountCoupon extends BaseCoupon
{
    public function discountType(): DiscountType
    {
        return DiscountType::FIXED;
    }


}