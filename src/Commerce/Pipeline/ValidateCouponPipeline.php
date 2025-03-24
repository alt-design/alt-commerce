<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon\ValidateCouponDates;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon\ValidateEligibleProducts;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\Customer;


class ValidateCouponPipeline extends Pipeline
{
    public function __construct(
       ValidateCouponDates $validateCouponDates,
       ValidateEligibleProducts $validateEligibleProducts,
    )
    {
        self::register(...func_get_args());
    }

    public function handle(Basket $basket, Coupon $coupon, Customer|null $customer = null): void
    {
        $this->run($basket, $coupon, $customer);
    }
}