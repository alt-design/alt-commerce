<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon\ValidateCouponDates;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon\ValidateEligibleProducts;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\Customer;


class ValidateCouponPipeline
{
    /**
     * @var array<mixed>
     */
    protected static array $stages = [];

    public function __construct(
       ValidateCouponDates $validateCouponDates,
       ValidateEligibleProducts $validateEligibleProducts,
    )
    {
        self::register(...func_get_args());
    }

    public function handle(Coupon $coupon, Basket $basket, Customer|null $customer = null): void
    {
        foreach (self::$stages as $stage) {
            $stage->handle($coupon, $basket, $customer);
        }
    }

    public static function register(object ...$job): void
    {
        array_push(self::$stages, ...$job);
    }
}