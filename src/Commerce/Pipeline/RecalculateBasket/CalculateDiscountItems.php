<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Enum\DiscountType;

class CalculateDiscountItems
{
    public function handle(Basket $basket): void
    {
        // remove coupon discounts items as they need to be recalculated
        foreach ($basket->discountItems as $key => $item) {
            if ($item instanceof CouponDiscountItem) {
                unset($basket->discountItems[$key]);
            }
        }

        foreach ($basket->coupons as $couponItem) {
            $discountAmount = $couponItem->coupon->discountType() === DiscountType::FIXED ?
                $couponItem->coupon->discountAmount() :
                $basket->subTotal * $couponItem->coupon->discountAmount() / 100;

            $basket->discountItems[] = new CouponDiscountItem(
                name: $couponItem->coupon->name(),
                amount: $discountAmount,
                coupon: $couponItem->coupon,
            );
        }
    }
}