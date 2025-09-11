<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Services\PriceCalculatorService\Service;

class CalculateLineItemTax
{
    public static array $skip = [];

    public function __construct(
        protected Service $priceCalculatorService,
    )
    {

    }

    public function handle(Basket $basket): void
    {
        foreach ($basket->lineItems as $lineItem) {

            if (in_array($lineItem->id, self::$skip)) {
                continue;
            }

            $response = $this->priceCalculatorService->calculate(
                currency: $basket->currency,
                amount: $lineItem->amount,
                amountInclusive: false,
                countryCode: $basket->countryCode,
                taxRules: $lineItem->taxRules,
            );

            if (empty($response->taxRate)) {
                continue;
            }

            $lineItem->taxTotal = $response->taxAmount;
            $lineItem->taxRate = $response->taxRule->rate;
            $lineItem->taxName = $response->taxRule->name;

        }
    }
}