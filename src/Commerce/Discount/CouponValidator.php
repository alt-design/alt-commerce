<?php

namespace AltDesign\AltCommerce\Commerce\Discount;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\ProductCoupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

class CouponValidator
{

    public function validate(Basket $basket, Coupon $coupon, Customer|null $customer = null): void
    {

        if ($coupon->startDate() && $coupon->startDate() > new \DateTimeImmutable()) {
            throw new CouponNotValidException(
                reason: CouponNotValidReason::NOT_YET_BEGUN
            );
        }

        if ($coupon->endDate() && $coupon->endDate() < new \DateTimeImmutable()) {
            throw new CouponNotValidException(
                reason: CouponNotValidReason::EXPIRED
            );
        }

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