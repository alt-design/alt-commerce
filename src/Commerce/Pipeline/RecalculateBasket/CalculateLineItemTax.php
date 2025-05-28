<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;

class CalculateLineItemTax
{
    public static array $skip = [];

    public function handle(Basket $basket): void
    {
        foreach ($basket->lineItems as $lineItem) {

            if (in_array($lineItem->id, self::$skip)) {
                continue;
            }

            $taxRules = [];
            foreach ($lineItem->taxRules as $taxRule) {
                if (!empty($taxRule->countryFilter) && !in_array($basket->countryCode, $taxRule->countryFilter)) {
                    continue;
                }
                $taxRules[] = $taxRule;
            }

            if (empty($taxRules)) {
                continue;
            }


            // For now only support first tax rule... Can't think of any scenario where we would need 2 tax rules.
            // We rely on the repository to order tax rules by priority.
            $taxRule = $taxRules[0];
            $lineItem->taxTotal = ($lineItem->subTotal - $lineItem->discountTotal) * $taxRule->rate / 100;
            $lineItem->taxRate = $taxRule->rate;
            $lineItem->taxName = $taxRule->name;

        }
    }
}