<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemSubtotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateProductCouponsDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTaxItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\ClearDiscounts;

class RecalculateBasketPipeline
{
    /**
     * @var array<mixed>
     */
    protected static array $stages = [];

    public function __construct(
        protected ClearDiscounts                   $clearDiscounts,
        protected CalculateLineItemSubtotals       $calculateLineItemSubtotals,
        protected CalculateProductCouponsDiscounts $calculateProductCouponDiscounts,
        protected CalculateLineItemTax             $calculateLineItemTax,
        protected CalculateTaxItems                $calculateTaxItems,
        protected CalculateTotals                  $calculateTotals,
    )
    {
        self::register(...func_get_args());
    }

    public function handle(Basket $basket): void
    {
        foreach (self::$stages as $stage) {
            $stage->handle($basket);
        }
    }

    public static function register(object ...$job): void
    {
        array_push(self::$stages, ...$job);
    }

}