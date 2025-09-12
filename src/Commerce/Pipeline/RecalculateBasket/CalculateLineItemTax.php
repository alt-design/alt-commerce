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

            $lineItem->taxTotal = $response->taxAmount * $lineItem->quantity;
            $lineItem->taxRate = $response->taxRule?->rate ?? 0;
            $lineItem->taxName = $response->taxRule?->name;

        }
    }
}