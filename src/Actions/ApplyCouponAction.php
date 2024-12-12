<?php

namespace AltDesign\AltCommerce\Actions;


use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Exceptions\CouponNotFoundException;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\RuleEngine\RuleManager;

class ApplyCouponAction
{

    public function __construct(
        protected BasketRepository $basketRepository,
        protected CouponRepository $couponRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
        protected RuleManager $ruleManager,
    )
    {

    }

    public function handle(string $coupon): Coupon
    {

        $basket = $this->basketRepository->get();

        $coupon = $this->couponRepository->find($basket->currency, $coupon);

        if (empty($coupon)) {
            throw new CouponNotFoundException();
        }

        $this->validate($basket, $coupon);

        // todo - support for multiple coupons?
        $basket->coupons = [
            new CouponItem(
                coupon: $coupon
            )
        ];

        $this->basketRepository->save($basket);

        $this->recalculateBasketAction->handle();

        return $coupon;
    }


    protected function validate(Basket $basket, Coupon $coupon): void
    {
        $result = $this->ruleManager->evaluate($coupon->ruleGroup(), context: [
            'basket' => $basket
        ]);

        if (!$result->result) {
            throw new CouponNotValidException();
        }
    }


}