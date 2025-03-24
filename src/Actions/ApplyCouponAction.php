<?php

namespace AltDesign\AltCommerce\Actions;


use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Discount\CouponValidator;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use Ramsey\Uuid\Uuid;

class ApplyCouponAction
{

    public function __construct(
        protected BasketRepository $basketRepository,
        protected CouponRepository $couponRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
        protected CouponValidator $couponValidator,
    )
    {

    }

    public function handle(string $coupon): Coupon
    {
        $basket = $this->basketRepository->get();

        $coupon = $this->couponRepository->find($basket->currency, $coupon);

        if (empty($coupon)) {
            throw new CouponNotValidException(
                reason: CouponNotValidReason::NOT_FOUND
            );
        }

        $this->couponValidator->validate($basket, $coupon);

        $basket->coupons = [
            new CouponItem(
                id: Uuid::uuid4()->toString(),
                coupon: $coupon
            )
        ];

        $this->recalculateBasketAction->handle();

        return $coupon;
    }
}