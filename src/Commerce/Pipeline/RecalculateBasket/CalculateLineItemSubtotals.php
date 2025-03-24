<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;

class CalculateLineItemSubtotals
{
    public function handle(Basket $basket): void
    {

        $basket->subTotal = 0;
        foreach ($basket->lineItems as $lineItem) {
            $lineItem->subTotal = $lineItem->amount * $lineItem->quantity;
            $basket->subTotal += $lineItem->subTotal;
        }
    }
}