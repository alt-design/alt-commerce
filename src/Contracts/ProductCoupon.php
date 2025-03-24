<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Enum\DiscountType;

interface ProductCoupon extends Coupon
{
    public function isProductEligible(string $productId): string;

}