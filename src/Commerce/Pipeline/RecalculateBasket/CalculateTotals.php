<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\DeliveryItem;
use AltDesign\AltCommerce\Commerce\Basket\FeeItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Contracts\DiscountItem;

class CalculateTotals
{
    public function handle(Basket $basket): void
    {
        $basket->taxTotal = array_reduce($basket->taxItems, fn($sum, TaxItem $item) => $sum + $item->amount, 0);
        $basket->deliveryTotal = array_reduce($basket->deliveryItems, fn($sum, DeliveryItem $item) => $sum + $item->amount, 0);
        $basket->feeTotal = array_reduce($basket->feeItems, fn($sum, FeeItem $item) => $sum + $item->amount, 0);
        $basket->discountTotal = array_reduce($basket->discountItems, fn($sum, DiscountItem $item) => $sum + $item->amount(), 0);
        $basket->total = max(
            $basket->subTotal +
            $basket->taxTotal +
            $basket->deliveryTotal +
            $basket->feeTotal -
            $basket->discountTotal, 0
        );
    }
}