<?php

namespace AltDesign\AltCommerce\Actions;


use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Exceptions\CouponNotFoundException;
use Illuminate\Support\Arr;


class RemoveCouponAction
{
    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    public function handle(string $code): Coupon
    {
        $basket = $this->basketRepository->get();

        $found = false;


        foreach ($basket->coupons as $key => $couponItem) {
            if ($couponItem->coupon->code() === $code) {
                unset($basket->coupons[$key]);
                $found =  $couponItem->coupon;
                break;
            }
        }

        if (!$found) {
            throw new CouponNotFoundException($code);
        }

        $this->basketRepository->save($basket);
        $this->recalculateBasketAction->handle();
        return $found;
    }
}