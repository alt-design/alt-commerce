<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\ProductCoupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

class ValidateEligibleProducts
{
    public function handle(Coupon $coupon, Basket $basket): void
    {
        if ($coupon instanceof ProductCoupon) {
            $eligible = false;
            foreach ($basket->lineItems as $lineItem) {
                if ($coupon->isProductEligible($lineItem->productId)) {
                    $eligible = true;
                    break;
                }
            }

            if (!$eligible) {
                throw new CouponNotValidException(
                    reason: CouponNotValidReason::NOT_ELIGIBLE
                );
            }
        }
    }
}