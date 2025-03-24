<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

class ValidateCouponDates
{
    public function handle(Coupon $coupon): void
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

    }
}