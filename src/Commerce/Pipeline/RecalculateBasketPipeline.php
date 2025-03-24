<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemSubtotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateProductCouponsDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTaxItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\ClearDiscounts;
use AltDesign\AltCommerce\Contracts\BasketRepository;

class RecalculateBasketPipeline
{
    public function __construct(
        protected BasketRepository                 $basketRepository,
        protected ClearDiscounts                   $clearDiscounts,
        protected CalculateProductCouponsDiscounts $calculateProductCouponDiscounts,
        protected CalculateLineItemSubtotals       $calculateLineItemSubtotals,
        protected CalculateLineItemTax             $calculateLineItemTax,
        protected CalculateTaxItems                $calculateTaxItems,
        protected CalculateTotals                  $calculateTotals,
    )
    {
    }

    public function handle(): void
    {
        $basket = $this->basketRepository->get();

        $stack = [
            $this->clearDiscounts,
            $this->calculateLineItemSubtotals,
            $this->calculateProductCouponDiscounts,
            $this->calculateLineItemTax,
            $this->calculateTaxItems,
            $this->calculateTotals,
        ];

        foreach ($stack as $job) {
            $job->handle($basket);
        }

        $this->basketRepository->save($basket);
    }
}