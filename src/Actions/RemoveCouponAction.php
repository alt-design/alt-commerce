<?php

namespace AltDesign\AltCommerce\Actions;


use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;


class RemoveCouponAction
{
    public function __construct(
        protected BasketContext $context,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    public function handle(string $code): Coupon
    {
        $basket = $this->context->current();

        $found = false;

        foreach ($basket->coupons as $key => $couponItem) {
            if ($couponItem->coupon->code() === $code) {
                unset($basket->coupons[$key]);
                $found =  $couponItem->coupon;
                break;
            }
        }

        if (!$found) {
            throw new CouponNotValidException(reason: CouponNotValidReason::NOT_FOUND);
        }

        $this->recalculateBasketAction->handle();
        return $found;
    }
}