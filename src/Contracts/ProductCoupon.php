<?php

namespace AltDesign\AltCommerce\Contracts;

interface ProductCoupon extends Coupon
{
    public function isProductEligible(string $productId): bool;

}
