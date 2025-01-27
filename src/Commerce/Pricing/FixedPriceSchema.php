<?php

namespace AltDesign\AltCommerce\Commerce\Pricing;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Support\PriceCollection;

class FixedPriceSchema implements PricingSchema
{

    public function __construct(protected PriceCollection $prices)
    {

    }

    public function hasBillingPlan(): bool
    {
        return false;
    }

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return int
     * @throws \AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException
     */
    public function getAmount(string $currency, array $context = []): int
    {
        return $this->prices->getAmount($currency);
    }

    public function isCurrencySupported(string $currency): bool
    {
       return $this->prices->isCurrencySupported($currency);
    }

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return BillingPlan
     * @throws \Exception
     */
    public function getBillingPlan(string $currency, array $context = []): BillingPlan
    {
        throw new \Exception('Not implemented');
    }

}