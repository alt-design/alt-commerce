<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Services\PriceCalculatorService\Service as PriceCalculatorService;

class CalculateTaxItems
{
    public function __construct(
        protected PriceCalculatorService $priceCalculatorService,
    )
    {

    }

    public function handle(Basket $basket): void
    {
        // tax items on the basket are simply line items tax grouped by name
        $basket->taxItems = [];
        foreach ($basket->lineItems as $lineItem) {
            if (intval($lineItem->taxRate) === 0 || empty($lineItem->taxName)) {
                continue;
            }

            $this->addTaxAmount($basket, $lineItem->taxName, $lineItem->taxRate, $lineItem->taxTotal);
        }

        // delivery items carrying tax rules are taxed on their (exclusive) amount
        foreach ($basket->deliveryItems as $deliveryItem) {
            if (empty($deliveryItem->taxRules) || $deliveryItem->amount <= 0) {
                continue;
            }

            $response = $this->priceCalculatorService->calculate(
                currency: $basket->currency,
                amount: $deliveryItem->amount,
                amountInclusive: false,
                countryCode: $basket->countryCode,
                taxRules: $deliveryItem->taxRules,
            );

            if (empty($response->taxRule) || $response->taxAmount <= 0) {
                continue;
            }

            $this->addTaxAmount($basket, $response->taxRule->name, $response->taxRule->rate, (int) $response->taxAmount);
        }
    }

    protected function addTaxAmount(Basket $basket, string $name, float|int|string $rate, int $amount): void
    {
        foreach ($basket->taxItems as $taxItem) {
            if ((float) $taxItem->rate === (float) $rate && $taxItem->name === $name) {
                $taxItem->amount += $amount;

                return;
            }
        }

        $basket->taxItems[] = new TaxItem(
            name: $name,
            amount: $amount,
            rate: $rate,
        );
    }
}
