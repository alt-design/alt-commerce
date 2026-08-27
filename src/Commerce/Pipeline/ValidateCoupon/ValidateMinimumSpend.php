<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

class ValidateMinimumSpend
{
    public function handle(Coupon $coupon, Basket $basket): void
    {
        if ($coupon->minimumSpend() <= 0) {
            return;
        }

        // Calculated from line items directly as the basket subtotal may be
        // stale or already discounted at the point of validation.
        $subTotal = 0;
        foreach ($basket->lineItems as $lineItem) {
            $subTotal += $lineItem->amount * $lineItem->quantity;
        }

        if ($subTotal < $coupon->minimumSpend()) {
            throw new CouponNotValidException(
                reason: CouponNotValidReason::MINIMUM_SPEND_NOT_MET
            );
        }
    }
}
