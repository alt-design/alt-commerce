<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Enum\DiscountType;

class ClearDiscounts
{
    public function handle(Basket $basket): void
    {
        $basket->discountTotal = 0;

        // Only want to clear discounts applied from product coupons
        $basket->discountItems = array_filter(
            $basket->discountItems,
            fn(DiscountItem $item) => $item->type !== DiscountType::PRODUCT_COUPON
        );
        foreach ($basket->lineItems as $lineItem) {
            $lineItem->discountTotal = 0;
            $lineItem->discounts = [];
        }
    }
}