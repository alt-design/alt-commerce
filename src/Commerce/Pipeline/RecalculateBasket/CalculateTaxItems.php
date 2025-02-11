<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;

class CalculateTaxItems
{
    public function handle(Basket $basket): void
    {
        // tax items on the basket are simply line items tax grouped by name
        $basket->taxItems = [];
        foreach ($basket->lineItems as $lineItem) {
            if (intval($lineItem->taxRate) === 0 || empty($lineItem->taxName)) {
                continue;
            }

            $existing = array_filter($basket->taxItems, fn(TaxItem $item) => $item->rate === $lineItem->taxRate && $item->name === $lineItem->taxName);

            if (!empty($existing)) {
                $basket->taxItems[array_key_first($existing)]->amount += $lineItem->taxTotal;
                continue;
            }

            $basket->taxItems[] = new TaxItem(
                name: $lineItem->taxName,
                amount: $lineItem->taxTotal,
                rate: $lineItem->taxRate,
            );
        }
    }
}