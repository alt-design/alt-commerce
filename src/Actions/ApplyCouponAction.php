<?php

namespace AltDesign\AltCommerce\Actions;


use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCouponPipeline;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use Ramsey\Uuid\Uuid;

class ApplyCouponAction
{

    public function __construct(
        protected BasketRepository $basketRepository,
        protected CouponRepository $couponRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
        protected ValidateCouponPipeline $validateCouponPipeline,
    )
    {

    }

    public function handle(string $coupon, Customer|null $customer = null): Coupon
    {
        $basket = $this->basketRepository->get();

        $coupon = $this->couponRepository->find($basket->currency, $coupon);

        if (empty($coupon)) {
            throw new CouponNotValidException(
                reason: CouponNotValidReason::NOT_FOUND
            );
        }

        $this->validateCouponPipeline->handle(
            basket: $basket,
            coupon: $coupon,
            customer: $customer
        );

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