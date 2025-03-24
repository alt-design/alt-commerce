<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCouponPipeline;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

class ValidateCoupons
{
    public function __construct(
        protected ValidateCouponPipeline $validateCouponPipeline
    ) {

    }

    public function handle(Basket $basket): void
    {
        foreach ($basket->coupons as $key => $couponItem) {
            try {
                $this->validateCouponPipeline->handle($couponItem->coupon, $basket);
            } catch (CouponNotValidException) {
                unset($basket->coupons[$key]);
            }
        }
    }
}