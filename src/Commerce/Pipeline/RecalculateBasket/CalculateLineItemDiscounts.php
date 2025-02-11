<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;

class CalculateLineItemDiscounts
{
    public function handle(Basket $basket): void
    {
        foreach ($basket->discountItems as $discountItem) {
            if ( !($discountItem instanceof CouponDiscountItem)) {
                continue;
            }

            $runningTotal = 0;
            $maxDiscountAmount = 0;
            $maxDiscountKey = null;
            foreach ($basket->lineItems as $key => $lineItem) {
                $weight = $lineItem->subTotal / $basket->subTotal;

                $discountTotal = intval($discountItem->amount() * $weight);
                $lineItem->discountTotal += $discountTotal;
                $runningTotal += $discountTotal;

                if ($discountTotal > $maxDiscountAmount) {
                    $maxDiscountKey = $key;
                    $maxDiscountAmount = $discountTotal;
                }
            }

            // adjust for rounding errors
            $diff = $runningTotal - $discountItem->amount();
            if ($diff !== 0) {
                $basket->lineItems[$maxDiscountKey]->discountTotal -= $diff;
            }
        }
    }
}