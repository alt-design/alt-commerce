<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;

class ClearDiscounts
{
    public function handle(Basket $basket): void
    {
        $basket->discountTotal = 0;
        $basket->discountItems = [];
        foreach ($basket->lineItems as $lineItem) {
            $lineItem->discountTotal = 0;
            $lineItem->discounts = [];
        }
    }
}