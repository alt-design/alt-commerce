<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Contracts\BasketRepository;

class RecalculateBasketPipeline
{
    public function __construct(
        protected BasketRepository $basketRepository,
        protected CalculateLineItemSubtotals $calculateLineItemSubtotals,
        protected CalculateDiscountItems $calculateDiscountItems,
        protected CalculateLineItemDiscounts $calculateLineItemDiscounts,
        protected CalculateLineItemTax $calculateLineItemTax,
        protected CalculateTaxItems $calculateTaxItems,
        protected CalculateTotals $calculateTotals,
    )
    {
    }

    public function handle(): void
    {
        $basket = $this->basketRepository->get();

        $stack = [
            $this->calculateLineItemSubtotals,
            $this->calculateDiscountItems,
            $this->calculateLineItemDiscounts,
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